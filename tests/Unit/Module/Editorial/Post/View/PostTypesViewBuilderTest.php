<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\View;

use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Post\View\PostTypesViewBuilder;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;
use PHPUnit\Framework\TestCase;

final class PostTypesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsPostTypesAndTaxonomies(): void
    {
        $postTypeRepo = $this->createStub(PostTypeRepository::class);
        $postTypeRepo->method('findAllWithRelations')->willReturn([$this->createStub(PostTypeInterface::class)]);

        $taxonomyRepo = $this->createStub(TaxonomyRepository::class);
        $taxonomyRepo->method('findAllWithTranslationsAndPostTypes')->willReturn([
            $this->createStub(TaxonomyInterface::class),
            $this->createStub(TaxonomyInterface::class),
        ]);

        $postTypeSerializer = $this->createStub(PostTypeSerializerInterface::class);
        $postTypeSerializer->method('serialize')->willReturn(['id' => 1]);

        $taxonomySerializer = $this->createStub(TaxonomySerializerInterface::class);
        $taxonomySerializer->method('serialize')->willReturn(['id' => 1]);

        $view = (new PostTypesViewBuilder(
            $postTypeRepo,
            $taxonomyRepo,
            $postTypeSerializer,
            $taxonomySerializer,
        ))->indexView();

        self::assertCount(1, $view['postTypes']);
        self::assertCount(2, $view['taxonomies']);
    }
}
