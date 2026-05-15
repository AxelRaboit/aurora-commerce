<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Taxonomy\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;
use Aurora\Module\Editorial\Taxonomy\View\TaxonomiesViewBuilder;
use PHPUnit\Framework\TestCase;

final class TaxonomiesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsTaxonomiesAndPostTypes(): void
    {
        $taxonomyRepo = $this->createStub(TaxonomyRepository::class);
        $taxonomyRepo->method('findAllForIndex')->willReturn([$this->createStub(TaxonomyInterface::class)]);

        $postTypeRepo = $this->createStub(PostTypeRepository::class);
        $postTypeRepo->method('findAllWithRelations')->willReturn([
            $this->createStub(PostTypeInterface::class),
            $this->createStub(PostTypeInterface::class),
        ]);

        $taxonomySerializer = $this->createStub(TaxonomySerializerInterface::class);
        $taxonomySerializer->method('serializeFull')->willReturn(['id' => 1]);

        $postTypeSerializer = $this->createStub(PostTypeSerializerInterface::class);
        $postTypeSerializer->method('serialize')->willReturn(['id' => 1]);

        $localeContext = $this->createStub(LocaleContextInterface::class);
        $localeContext->method('getActiveLocales')->willReturn(['fr', 'en']);

        $view = (new TaxonomiesViewBuilder(
            $taxonomyRepo,
            $postTypeRepo,
            $taxonomySerializer,
            $postTypeSerializer,
            $localeContext,
        ))->indexView();

        self::assertCount(1, $view['taxonomies']);
        self::assertCount(2, $view['postTypes']);
        self::assertSame(['fr', 'en'], $view['locales']);
    }
}
