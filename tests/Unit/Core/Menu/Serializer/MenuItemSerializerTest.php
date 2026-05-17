<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Menu\Serializer;

use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Core\Menu\Serializer\MenuItemSerializer;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MenuItemSerializerTest extends TestCase
{
    public function testSerializeProjectsAllCoreFieldsAndTranslations(): void
    {
        $item = $this->makeItem(id: 7, targetType: MenuItemTargetTypeEnum::CustomUrl, customUrl: 'https://example.com', cssClass: 'highlight', position: 3, openInNewTab: true);
        $this->addTranslation($item, 'fr', 'Accueil');
        $this->addTranslation($item, 'en', 'Home');

        $serializer = $this->makeSerializer();

        $payload = $serializer->serialize($item);

        self::assertSame(7, $payload['id']);
        self::assertSame('custom_url', $payload['targetType']);
        self::assertNull($payload['targetId']);
        self::assertSame('https://example.com', $payload['customUrl']);
        self::assertTrue($payload['openInNewTab']);
        self::assertSame('highlight', $payload['cssClass']);
        self::assertSame('always', $payload['visibility']);
        self::assertSame(3, $payload['position']);
        self::assertNull($payload['parentId']);
        self::assertSame(['fr' => 'Accueil', 'en' => 'Home'], $payload['translations']);
    }

    public function testSerializeRecursivelyEmitsChildren(): void
    {
        $root = $this->makeItem(id: 1);
        $child = $this->makeItem(id: 2);
        $child->setParent($root);
        $root->getChildren()->add($child);
        $grandchild = $this->makeItem(id: 3);
        $grandchild->setParent($child);
        $child->getChildren()->add($grandchild);

        $serializer = $this->makeSerializer();

        $payload = $serializer->serialize($root);

        self::assertCount(1, $payload['children']);
        self::assertSame(2, $payload['children'][0]['id']);
        self::assertCount(1, $payload['children'][0]['children']);
        self::assertSame(3, $payload['children'][0]['children'][0]['id']);
    }

    public function testTargetPreviewForStaticHomeUsesTranslator(): void
    {
        // Static targets (Home/Login/Register/Account/Logout/Shop) skip
        // the cache and call the translator directly with a hard-coded
        // hint path.
        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::Home);

        $serializer = $this->makeSerializer(translatorMap: ['frontend.menu.home' => 'Accueil']);

        $preview = $serializer->serialize($item)['targetPreview'];

        self::assertSame('Accueil', $preview['label']);
        self::assertSame('/', $preview['hint']);
    }

    public function testTargetPreviewForPostUsesCacheWhenAvailable(): void
    {
        $post = $this->makePost(id: 42, slug: 'hello-world', title: 'Hello World', postTypeSlug: 'article');
        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::Post, targetId: 42);

        $serializer = $this->makeSerializer();

        $payload = $serializer->serialize($item, postCache: [42 => $post]);

        self::assertSame('Hello World', $payload['targetPreview']['label']);
        self::assertSame('/article/hello-world', $payload['targetPreview']['hint']);
        self::assertArrayNotHasKey('missing', $payload['targetPreview']);
    }

    public function testTargetPreviewForMissingPostMarksMissingAndReturnsFallbackLabel(): void
    {
        // Pointed-to post was deleted — preview must flag `missing: true`
        // so the admin UI can render a strike-through with a fix CTA.
        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::Post, targetId: 999);

        $postRepo = $this->createStub(PostRepository::class);
        $postRepo->method('find')->willReturn(null);

        $serializer = $this->makeSerializer(
            postRepo: $postRepo,
            translatorMap: ['backend.menus.preview.post_deleted' => '(deleted)'],
        );

        $preview = $serializer->serialize($item)['targetPreview'];

        self::assertTrue($preview['missing']);
        self::assertSame('(deleted)', $preview['label']);
        self::assertSame('#999', $preview['hint']);
    }

    public function testTargetPreviewForTermResolvesFromCache(): void
    {
        $term = $this->makeTerm(id: 30, taxonomySlug: 'category', name: 'News', slug: 'news');
        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::Term, targetId: 30);

        $serializer = $this->makeSerializer();

        $preview = $serializer->serialize($item, termCache: [30 => $term])['targetPreview'];

        self::assertSame('News', $preview['label']);
        self::assertSame('/category/news', $preview['hint']);
    }

    public function testTargetPreviewForPostTypeArchiveResolvesFromCache(): void
    {
        $postType = new PostType();
        (new ReflectionProperty(PostType::class, 'id'))->setValue($postType, 7);
        $postType->setSlug('events');
        $postType->setLabel('Events');

        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::PostTypeArchive, targetId: 7);

        $serializer = $this->makeSerializer();

        $preview = $serializer->serialize($item, postTypeCache: [7 => $postType])['targetPreview'];

        self::assertSame('Events', $preview['label']);
        self::assertSame('/events', $preview['hint']);
    }

    public function testTargetPreviewForCustomUrlEchoesUrl(): void
    {
        $item = $this->makeItem(id: 1, targetType: MenuItemTargetTypeEnum::CustomUrl, customUrl: 'https://example.com/x');

        $serializer = $this->makeSerializer();

        $preview = $serializer->serialize($item)['targetPreview'];

        self::assertSame('https://example.com/x', $preview['label']);
        self::assertSame('https://example.com/x', $preview['hint']);
    }

    public function testPreloadTargetsBatchesByTypeAndDeduplicatesIds(): void
    {
        $post1 = $this->makePost(id: 1, slug: 'a', title: 'A', postTypeSlug: 'p');
        $post2 = $this->makePost(id: 2, slug: 'b', title: 'B', postTypeSlug: 'p');
        $term1 = $this->makeTerm(id: 10, taxonomySlug: 'cat', name: 'X', slug: 'x');

        $postRepo = $this->createMock(PostRepository::class);
        $postRepo->expects(self::once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn([$post1, $post2]);

        $termRepo = $this->createMock(TaxonomyTermRepository::class);
        $termRepo->expects(self::once())
            ->method('findByIds')
            ->with([10])
            ->willReturn([$term1]);

        $postTypeRepo = $this->createMock(PostTypeRepository::class);
        $postTypeRepo->expects(self::once())->method('findByIds')->with([])->willReturn([]);

        $serializer = $this->makeSerializer(postRepo: $postRepo, termRepo: $termRepo, postTypeRepo: $postTypeRepo);

        // Build an item tree mixing target types and a duplicate post id
        // (1 referenced twice) — the dedupe in preloadTargets means a
        // single SQL query, not two.
        $rootPost = $this->makeItem(id: 100, targetType: MenuItemTargetTypeEnum::Post, targetId: 1);
        $childPost = $this->makeItem(id: 101, targetType: MenuItemTargetTypeEnum::Post, targetId: 2);
        $childPost->setParent($rootPost);
        $rootPost->getChildren()->add($childPost);

        $rootTerm = $this->makeItem(id: 200, targetType: MenuItemTargetTypeEnum::Term, targetId: 10);
        $dupPost = $this->makeItem(id: 201, targetType: MenuItemTargetTypeEnum::Post, targetId: 1); // duplicate
        $dupPost->setParent($rootTerm);
        $rootTerm->getChildren()->add($dupPost);

        $cache = $serializer->preloadTargets([$rootPost, $rootTerm]);

        self::assertSame([1, 2], array_keys($cache['posts']));
        self::assertSame([10], array_keys($cache['terms']));
        self::assertSame([], array_keys($cache['postTypes']));
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeItem(
        int $id,
        MenuItemTargetTypeEnum $targetType = MenuItemTargetTypeEnum::Home,
        ?int $targetId = null,
        ?string $customUrl = null,
        ?string $cssClass = null,
        int $position = 0,
        bool $openInNewTab = false,
    ): MenuItem {
        $item = new MenuItem();
        (new ReflectionProperty(MenuItem::class, 'id'))->setValue($item, $id);
        $item->setTargetType($targetType);
        $item->setTargetId($targetId);
        $item->setCustomUrl($customUrl);
        $item->setOpenInNewTab($openInNewTab);
        $item->setCssClass($cssClass);
        $item->setVisibility(MenuItemVisibilityEnum::Always);
        $item->setPosition($position);

        return $item;
    }

    private function addTranslation(MenuItem $item, string $locale, string $label): void
    {
        $translation = new MenuItemTranslation();
        $translation->setLocale($locale);
        $translation->setLabel($label);
        $item->getTranslations()->add($translation);
    }

    private function makePost(int $id, string $slug, string $title, string $postTypeSlug): Post
    {
        $postType = new PostType();
        $postType->setSlug($postTypeSlug);
        $postType->setLabel('PT');

        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, $id);
        $post->setPostType($postType);

        $translation = new PostTranslation();
        $translation->setPost($post);
        $translation->setLocale('fr');
        $translation->setTitle($title);
        $translation->setSlug($slug);
        $post->getTranslations()->set('fr', $translation);

        return $post;
    }

    private function makeTerm(int $id, string $taxonomySlug, string $name, string $slug): TaxonomyTerm
    {
        $taxonomy = new Taxonomy();
        $taxonomy->setSlug($taxonomySlug);
        $taxonomy->setHierarchical(false);

        $term = new TaxonomyTerm();
        (new ReflectionProperty(TaxonomyTerm::class, 'id'))->setValue($term, $id);
        $term->setTaxonomy($taxonomy);

        $translation = new TaxonomyTermTranslation();
        $translation->setLocale('fr');
        $translation->setName($name);
        $translation->setSlug($slug);
        $term->getTranslations()->set('fr', $translation);

        return $term;
    }

    /** @param array<string, string> $translatorMap */
    private function makeSerializer(
        ?PostRepository $postRepo = null,
        ?TaxonomyTermRepository $termRepo = null,
        ?PostTypeRepository $postTypeRepo = null,
        array $translatorMap = [],
    ): MenuItemSerializer {
        if (null === $postRepo) {
            $postRepo = $this->createStub(PostRepository::class);
            $postRepo->method('findByIds')->willReturn([]);
            $postRepo->method('find')->willReturn(null);
        }
        if (null === $termRepo) {
            $termRepo = $this->createStub(TaxonomyTermRepository::class);
            $termRepo->method('findByIds')->willReturn([]);
            $termRepo->method('find')->willReturn(null);
        }
        if (null === $postTypeRepo) {
            $postTypeRepo = $this->createStub(PostTypeRepository::class);
            $postTypeRepo->method('findByIds')->willReturn([]);
            $postTypeRepo->method('find')->willReturn(null);
        }

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $key): string => $translatorMap[$key] ?? "tr({$key})",
        );

        return new MenuItemSerializer($postRepo, $termRepo, $postTypeRepo, $translator);
    }
}
