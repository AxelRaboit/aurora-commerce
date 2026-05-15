<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Listing\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Ecommerce\Listing\View\ListingsViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsListingsAndPaths(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $pagination = new PaginationRequest(1, 20, 'search');
        $listPayload = ['items' => [], 'total' => 0];

        $view = (new ListingsViewBuilder($urlGenerator))->indexView($pagination, $listPayload);

        self::assertSame($listPayload, $view['listings']);
        self::assertSame('search', $view['search']);
        self::assertArrayHasKey('createPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
        self::assertArrayHasKey('showPath', $view);
    }

    public function testIndexViewWithNullSearchUsesEmptyString(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/path');

        $pagination = new PaginationRequest(1, 20, null);
        $view = (new ListingsViewBuilder($urlGenerator))->indexView($pagination, []);

        self::assertSame('', $view['search']);
    }
}
