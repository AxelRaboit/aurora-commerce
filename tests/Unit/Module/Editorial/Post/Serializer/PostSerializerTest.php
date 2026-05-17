<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Serializer;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Media\Library\Entity\Media;
use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Editorial\Post\Entity\AbstractPost;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Serializer\PostSerializer;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class PostSerializerTest extends TestCase
{
    private PostSerializer $serializer;
    private LocaleContextInterface $localeContext;
    private MediaUrlGenerator $mediaUrlGenerator;

    protected function setUp(): void
    {
        $this->localeContext = $this->createStub(LocaleContextInterface::class);
        $this->localeContext->method('getDefaultLocale')->willReturn('fr');

        // MediaUrlGenerator is `final readonly` (can't be stubbed) so we
        // build the real thing on top of a stubbed UrlGeneratorInterface
        // that echoes a predictable URL — covers both `publicUrl()` and
        // `variantUrl()` paths.
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params = []): string => '/'.($params['path'] ?? ''),
        );
        $this->mediaUrlGenerator = new MediaUrlGenerator($urlGenerator);

        $this->serializer = new PostSerializer($this->localeContext, $this->mediaUrlGenerator);
    }

    public function testSerializeProjectsTopLevelFieldsAndDefaultLocaleTitle(): void
    {
        $post = $this->makePost(id: 42, version: 3, status: PostStatusEnum::Published);
        $this->addTranslation($post, 'fr', title: 'Bonjour', slug: 'bonjour');
        $this->addTranslation($post, 'en', title: 'Hello', slug: 'hello');

        $payload = $this->serializer->serialize($post);

        self::assertSame(42, $payload['id']);
        self::assertSame(3, $payload['version']);
        self::assertSame('published', $payload['status']);
        // Title + slug come from the default locale (`fr`), not the
        // first translation added.
        self::assertSame('Bonjour', $payload['title']);
        self::assertSame('bonjour', $payload['slug']);
        self::assertFalse($payload['trashed']);
    }

    public function testSerializeIncludesPostTypeMetadata(): void
    {
        $post = $this->makePost(postType: $this->makePostType(id: 7, slug: 'article', label: 'Article'));

        $payload = $this->serializer->serialize($post);

        self::assertSame(['id' => 7, 'label' => 'Article', 'slug' => 'article'], $payload['postType']);
    }

    public function testSerializeFlattensTermAndRelatedPostIds(): void
    {
        $post = $this->makePost();
        $post->addTerm($this->makeTerm(11));
        $post->addTerm($this->makeTerm(12));
        $post->addRelatedPost($this->makePost(id: 99));

        $payload = $this->serializer->serialize($post);

        self::assertSame([11, 12], $payload['termIds']);
        self::assertSame([99], $payload['relatedPostIds']);
    }

    public function testSerializeFormatsTimestampsAsAtomAndPropagatesTrashed(): void
    {
        $post = $this->makePost();
        $post->setPublishedAt(new DateTimeImmutable('2026-04-01T10:00:00+00:00'));
        $post->setScheduledAt(new DateTimeImmutable('2026-04-15T09:00:00+00:00'));
        $post->setDeletedAt(new DateTimeImmutable('2026-04-20T08:00:00+00:00'));

        $payload = $this->serializer->serialize($post);

        self::assertSame((new DateTimeImmutable('2026-04-01T10:00:00+00:00'))->format(DateTimeInterface::ATOM), $payload['publishedAt']);
        self::assertSame((new DateTimeImmutable('2026-04-15T09:00:00+00:00'))->format(DateTimeInterface::ATOM), $payload['scheduledAt']);
        self::assertSame((new DateTimeImmutable('2026-04-20T08:00:00+00:00'))->format(DateTimeInterface::ATOM), $payload['deletedAt']);
        self::assertTrue($payload['trashed']);
    }

    public function testSerializeUsesNullTitleSlugWhenDefaultLocaleMissing(): void
    {
        // Edge case: only `en` translation, but default locale is `fr`.
        // Top-level title/slug fall back to null (the front handles the
        // "no translation in current locale" copy).
        $post = $this->makePost();
        $this->addTranslation($post, 'en', title: 'Only EN');

        $payload = $this->serializer->serialize($post);

        self::assertNull($payload['title']);
        self::assertNull($payload['slug']);
    }

    public function testSerializeFullIncludesEveryTranslation(): void
    {
        $post = $this->makePost(id: 5);
        $this->addTranslation($post, 'fr', title: 'FR title', blocks: [
            ['id' => 'b1', 'type' => 'paragraph', 'data' => ['text' => 'Bonjour']],
        ]);
        $this->addTranslation($post, 'en', title: 'EN title', metaDescription: 'Greeting');

        $payload = $this->serializer->serializeFull($post);

        self::assertArrayHasKey('fr', $payload['translations']);
        self::assertArrayHasKey('en', $payload['translations']);
        self::assertSame('FR title', $payload['translations']['fr']['title']);
        self::assertSame('EN title', $payload['translations']['en']['title']);
        self::assertSame('Greeting', $payload['translations']['en']['metaDescription']);
        self::assertCount(1, $payload['translations']['fr']['blocks']);
    }

    public function testSerializeFullIncludesFeaturedMediaUrlAndFocalPosition(): void
    {
        $media = $this->makeMedia(15, path: 'agency/15.jpg');
        $post = $this->makePost();
        $post->setFeaturedMedia($media);

        $payload = $this->serializer->serializeFull($post);

        self::assertSame(15, $payload['featuredMediaId']);
        self::assertSame('/agency/15.jpg', $payload['featuredMediaUrl']);
        self::assertSame('50% 50%', $payload['featuredMediaFocalPosition']);
    }

    public function testSerializeFullEmitsNullFeaturedMediaFieldsWhenAbsent(): void
    {
        $post = $this->makePost();

        $payload = $this->serializer->serializeFull($post);

        self::assertNull($payload['featuredMediaId']);
        self::assertNull($payload['featuredMediaUrl']);
        self::assertNull($payload['featuredMediaFocalPosition']);
    }

    public function testSerializeFullProjectsRelatedPostsCompactly(): void
    {
        $related = $this->makePost(id: 200, postType: $this->makePostType(label: 'Page'));
        $this->addTranslation($related, 'fr', title: 'Linked page');

        $post = $this->makePost(id: 1);
        $post->addRelatedPost($related);

        $payload = $this->serializer->serializeFull($post);

        self::assertCount(1, $payload['relatedPosts']);
        self::assertSame([
            'id' => 200,
            'title' => 'Linked page',
            'status' => 'draft',
            'postType' => 'Page',
        ], $payload['relatedPosts'][0]);
    }

    public function testSerializeReferenceFallsBackToFirstTranslationWhenDefaultMissing(): void
    {
        // The reference projection has its own title-fallback logic:
        // try the default locale, else use the first available
        // translation. Important for posts authored entirely in `en`
        // when the app's default is `fr`.
        $post = $this->makePost(id: 42, status: PostStatusEnum::Draft);
        $this->addTranslation($post, 'en', title: 'EN only');

        $payload = $this->serializer->serializeReference($post);

        self::assertSame('EN only', $payload['title']);
        self::assertSame(42, $payload['id']);
        self::assertSame('draft', $payload['status']);
    }

    public function testSerializeReferenceReturnsNullTitleWhenPostHasNoTranslations(): void
    {
        $post = $this->makePost(id: 1);

        self::assertNull($this->serializer->serializeReference($post)['title']);
    }

    public function testSerializeCardPicksTheRequestedLocaleAndVariantUrl(): void
    {
        // Frontend cards take an explicit `locale` (route-derived) and
        // prefer the `medium` variant URL when available.
        $media = $this->makeMedia(33, path: 'a.jpg', variantPath: ['medium' => 'a-medium.webp']);
        $post = $this->makePost(id: 50, postType: $this->makePostType(slug: 'news'));
        $post->setPublishedAt(new DateTimeImmutable('2026-05-10T12:00:00+00:00'));
        $post->setFeaturedMedia($media);
        $this->addTranslation($post, 'en', title: 'English card', slug: 'english-card', metaDescription: 'Snippet');

        $payload = $this->serializer->serializeCard($post, 'en');

        self::assertSame(50, $payload['id']);
        self::assertSame('English card', $payload['title']);
        self::assertSame('english-card', $payload['slug']);
        self::assertSame('Snippet', $payload['metaDescription']);
        self::assertSame('news', $payload['postTypeSlug']);
        self::assertSame('/a-medium.webp', $payload['featuredMediaUrl']);
        self::assertSame((new DateTimeImmutable('2026-05-10T12:00:00+00:00'))->format(DateTimeInterface::ATOM), $payload['publishedAt']);
    }

    public function testSerializeCardFallsBackToPublicUrlWhenMediumVariantMissing(): void
    {
        // Media has no `medium` variant generated yet — serializer falls
        // back to `publicUrl()` (the original).
        $media = $this->makeMedia(7, path: 'original.png');

        $post = $this->makePost();
        $post->setFeaturedMedia($media);
        $this->addTranslation($post, 'fr', title: 'T');

        $payload = $this->serializer->serializeCard($post, 'fr');

        self::assertSame('/original.png', $payload['featuredMediaUrl']);
    }

    public function testSerializeCardEmitsNullMediaFieldsWhenNoFeaturedMedia(): void
    {
        $post = $this->makePost();
        $this->addTranslation($post, 'fr', title: 'T');

        $payload = $this->serializer->serializeCard($post, 'fr');

        self::assertNull($payload['featuredMediaUrl']);
        self::assertNull($payload['featuredMediaFocalPosition']);
    }

    // ── Fixture helpers ─────────────────────────────────────────────────

    private function makePost(
        int $id = 1,
        int $version = 1,
        PostStatusEnum $status = PostStatusEnum::Draft,
        ?PostType $postType = null,
    ): Post {
        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, $id);
        (new ReflectionProperty(AbstractPost::class, 'version'))->setValue($post, $version);
        $post->setStatus($status);
        $post->setPostType($postType ?? $this->makePostType());

        // Timestamps are read by `serialize()` so they must be set even
        // for tests that don't care about their value.
        (new ReflectionProperty(AbstractPost::class, 'createdAt'))->setValue($post, new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        (new ReflectionProperty(AbstractPost::class, 'updatedAt'))->setValue($post, new DateTimeImmutable('2026-01-02T00:00:00+00:00'));

        return $post;
    }

    private function makePostType(int $id = 1, string $slug = 'article', string $label = 'Article'): PostType
    {
        $postType = new PostType();
        (new ReflectionProperty(PostType::class, 'id'))->setValue($postType, $id);
        $postType->setSlug($slug);
        $postType->setLabel($label);

        return $postType;
    }

    private function makeTerm(int $id): TaxonomyTermInterface
    {
        $term = new TaxonomyTerm();
        (new ReflectionProperty(TaxonomyTerm::class, 'id'))->setValue($term, $id);

        return $term;
    }

    /** @param array<string, string> $variantPath */
    private function makeMedia(int $id, string $path = 'pic.jpg', array $variantPath = []): Media
    {
        $media = new Media();
        (new ReflectionProperty(Media::class, 'id'))->setValue($media, $id);
        $media->setPath($path);
        $media->setVariants($variantPath);

        return $media;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks Editor.js native shape
     */
    private function addTranslation(
        PostInterface $post,
        string $locale,
        ?string $title = null,
        ?string $slug = null,
        array $blocks = [],
        ?string $metaDescription = null,
    ): PostTranslation {
        $translation = $post->translate($locale);
        $translation->setTitle($title);
        $translation->setSlug($slug);
        $translation->setBlocks($blocks);
        $translation->setMetaDescription($metaDescription);

        return $translation;
    }
}
