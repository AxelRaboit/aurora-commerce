<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingTag\View;

use Aurora\Core\Locale\Service\LocaleOptionsProviderInterface;
use Aurora\Module\Ecommerce\ListingTag\View\ListingTagsViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingTagsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsTagsAndPaths(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $localeProvider = $this->createStub(LocaleOptionsProviderInterface::class);
        $localeProvider->method('getActiveOptions')->willReturn([['code' => 'fr', 'label' => 'Français']]);

        $tags = [['id' => 1]];
        $view = (new ListingTagsViewBuilder($urlGenerator, $localeProvider))->indexView($tags);

        self::assertSame($tags, $view['tags']);
        self::assertNotEmpty($view['locales']);
        self::assertArrayHasKey('listPath', $view);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
    }
}
