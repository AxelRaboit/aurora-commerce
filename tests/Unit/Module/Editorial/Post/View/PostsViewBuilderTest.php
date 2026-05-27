<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Aurora\Module\Editorial\Post\View\PostsViewBuilder;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;
use Aurora\Module\Editorial\PostType\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class PostsViewBuilderTest extends TestCase
{
    public function testBuildListPayloadShapesPaginationResultIntoEnvelope(): void
    {
        $post = $this->createStub(PostInterface::class);

        $postRepo = $this->createMock(PostRepository::class);
        $postRepo->expects(self::once())
            ->method('findPaginated')
            ->with(2, 'fr', 10, 'cats', [4], trashed: true, authorId: null, termIds: [9], statuses: ['draft'])
            ->willReturn(['items' => [$post, $post], 'total' => 17, 'page' => 2, 'totalPages' => 2]);

        $postSerializer = $this->createStub(PostSerializerInterface::class);
        $postSerializer->method('serialize')->willReturn(['id' => 7]);

        $builder = $this->makeBuilder($postRepo, $postSerializer);

        $pagination = new PaginationRequest(page: 2, limit: 10, search: 'cats');
        $payload = $builder->buildListPayload($pagination, postTypeIds: [4], trashed: true, termIds: [9], statuses: ['draft']);

        self::assertTrue($payload['success']);
        self::assertCount(2, $payload['items']);
        self::assertSame(['id' => 7], $payload['items'][0]);
        self::assertSame(17, $payload['total']);
        self::assertSame(2, $payload['page']);
        self::assertSame(2, $payload['totalPages']);
    }

    public function testIndexViewBundlesListWithPostTypesTaxonomiesAndFilters(): void
    {
        $postTypeRepo = $this->createStub(PostTypeRepository::class);
        $postTypeRepo->method('findAllWithRelations')->willReturn([$this->createStub(PostTypeInterface::class)]);

        $taxonomyRepo = $this->createStub(TaxonomyRepository::class);
        $taxonomyRepo->method('findAllForIndex')->willReturn([
            $this->createStub(TaxonomyInterface::class),
            $this->createStub(TaxonomyInterface::class),
        ]);

        $postTypeSerializer = $this->createStub(PostTypeSerializerInterface::class);
        $postTypeSerializer->method('serialize')->willReturn(['id' => 1]);

        $taxonomySerializer = $this->createStub(TaxonomySerializerInterface::class);
        $taxonomySerializer->method('serializeFull')->willReturn(['id' => 1]);

        $localeContext = $this->createStub(LocaleContextInterface::class);
        $localeContext->method('getActiveLocales')->willReturn(['fr', 'en']);

        $builder = new PostsViewBuilder(
            $postTypeRepo,
            $taxonomyRepo,
            $postTypeSerializer,
            $taxonomySerializer,
            $this->createStub(PostRepository::class),
            $this->createStub(PostSerializerInterface::class),
            $localeContext,
        );

        $listPayload = ['success' => true, 'items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];
        $view = $builder->indexView(
            $listPayload,
            new PaginationRequest(page: 1, limit: 10, search: 'q'),
            trashed: false,
            postTypeIds: [2, 3],
            termIds: [10],
            statuses: ['published'],
        );

        self::assertSame($listPayload, $view['posts']);
        self::assertSame('q', $view['search']);
        self::assertCount(1, $view['postTypes']);
        self::assertCount(2, $view['taxonomies']);
        self::assertFalse($view['trashed']);
        self::assertSame(['fr', 'en'], $view['locales']);
        self::assertSame([2, 3], $view['postTypeIds']);
        self::assertSame([10], $view['termIds']);
        self::assertSame(['published'], $view['statuses']);
    }

    public function testIndexViewEmptySearchSerializesToEmptyString(): void
    {
        // `PaginationRequest::$search` can be null; the view must
        // expose an empty string (template-friendly) rather than null.
        $builder = $this->makeBuilder();

        $view = $builder->indexView(
            ['success' => true, 'items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1],
            new PaginationRequest(page: 1, limit: 10, search: null),
            trashed: false,
        );

        self::assertSame('', $view['search']);
    }

    public function testEditViewWithNullPostReturnsCreateModePayload(): void
    {
        // GET /backend/posts/new — `$post` is null, so the editor opens
        // in create mode. Repos for postTypes / taxonomies still fire
        // (the form needs them to populate selects), but `post` itself
        // is null.
        $postTypeRepo = $this->createStub(PostTypeRepository::class);
        $postTypeRepo->method('findAllWithRelations')->willReturn([$this->createStub(PostTypeInterface::class)]);

        $taxonomyRepo = $this->createStub(TaxonomyRepository::class);
        $taxonomyRepo->method('findAllForIndex')->willReturn([$this->createStub(TaxonomyInterface::class)]);

        $postSerializer = $this->createMock(PostSerializerInterface::class);
        $postSerializer->expects(self::never())->method('serializeFull');

        $builder = new PostsViewBuilder(
            $postTypeRepo,
            $taxonomyRepo,
            $this->stubSerializer(['id' => 1]),
            $this->stubTaxonomySerializer(['id' => 5]),
            $this->createStub(PostRepository::class),
            $postSerializer,
            $this->localeContext(['fr', 'en']),
        );

        $view = $builder->editView(null);

        self::assertNull($view['post']);
        self::assertCount(1, $view['postTypes']);
        self::assertCount(1, $view['taxonomies']);
        self::assertSame(['fr', 'en'], $view['locales']);
    }

    public function testEditViewWithExistingPostSerializesItFull(): void
    {
        // GET /backend/posts/{id}/edit — `$post` is the loaded entity,
        // serialized via `serializeFull` (not `serialize`) so the editor
        // gets translations + relatedPosts + featured media.
        $post = $this->createStub(PostInterface::class);

        $postSerializer = $this->createMock(PostSerializerInterface::class);
        $postSerializer->expects(self::once())
            ->method('serializeFull')
            ->with($post)
            ->willReturn(['id' => 99, 'translations' => []]);

        $builder = new PostsViewBuilder(
            $this->stubPostTypeRepo(),
            $this->stubTaxonomyRepo(),
            $this->stubSerializer(['id' => 1]),
            $this->stubTaxonomySerializer(['id' => 5]),
            $this->createStub(PostRepository::class),
            $postSerializer,
            $this->localeContext(['fr']),
        );

        $view = $builder->editView($post);

        self::assertSame(['id' => 99, 'translations' => []], $view['post']);
        self::assertSame(['fr'], $view['locales']);
    }

    private function makeBuilder(
        ?PostRepository $postRepo = null,
        ?PostSerializerInterface $postSerializer = null,
    ): PostsViewBuilder {
        return new PostsViewBuilder(
            $this->stubPostTypeRepo(),
            $this->stubTaxonomyRepo(),
            $this->stubSerializer(['id' => 1]),
            $this->stubTaxonomySerializer(['id' => 5]),
            $postRepo ?? $this->createStub(PostRepository::class),
            $postSerializer ?? $this->createStub(PostSerializerInterface::class),
            $this->localeContext(['fr']),
        );
    }

    private function stubPostTypeRepo(): PostTypeRepository
    {
        $stub = $this->createStub(PostTypeRepository::class);
        $stub->method('findAllWithRelations')->willReturn([]);

        return $stub;
    }

    private function stubTaxonomyRepo(): TaxonomyRepository
    {
        $stub = $this->createStub(TaxonomyRepository::class);
        $stub->method('findAllForIndex')->willReturn([]);

        return $stub;
    }

    /** @param array<string, mixed> $payload */
    private function stubSerializer(array $payload): PostTypeSerializerInterface
    {
        $stub = $this->createStub(PostTypeSerializerInterface::class);
        $stub->method('serialize')->willReturn($payload);

        return $stub;
    }

    /** @param array<string, mixed> $payload */
    private function stubTaxonomySerializer(array $payload): TaxonomySerializerInterface
    {
        $stub = $this->createStub(TaxonomySerializerInterface::class);
        $stub->method('serializeFull')->willReturn($payload);

        return $stub;
    }

    /** @param list<string> $locales */
    private function localeContext(array $locales): LocaleContextInterface
    {
        $stub = $this->createStub(LocaleContextInterface::class);
        $stub->method('getDefaultLocale')->willReturn($locales[0] ?? 'fr');
        $stub->method('getActiveLocales')->willReturn($locales);

        return $stub;
    }
}
