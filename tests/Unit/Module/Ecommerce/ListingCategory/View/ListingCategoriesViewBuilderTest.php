<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingCategory\View;

use Aurora\Core\Locale\Service\LocaleOptionsProviderInterface;
use Aurora\Module\Ecommerce\ListingCategory\View\ListingCategoriesViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingCategoriesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsCategoriesAndPaths(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $localeProvider = $this->createStub(LocaleOptionsProviderInterface::class);
        $localeProvider->method('getActiveOptions')->willReturn([['code' => 'fr', 'label' => 'Français']]);

        $categories = [['id' => 1]];
        $view = (new ListingCategoriesViewBuilder($urlGenerator, $localeProvider))->indexView($categories);

        self::assertSame($categories, $view['categories']);
        self::assertNotEmpty($view['locales']);
        self::assertArrayHasKey('listPath', $view);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
        self::assertArrayHasKey('reorderPath', $view);
    }
}
