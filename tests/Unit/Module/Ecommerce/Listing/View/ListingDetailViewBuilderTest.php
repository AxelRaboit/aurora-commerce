<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Listing\View;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializerInterface;
use Aurora\Module\Ecommerce\Listing\View\ListingDetailViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsSerializedListingAndPaths(): void
    {
        $listing = $this->createStub(ListingInterface::class);
        $listing->method('getId')->willReturn(42);

        $serializer = $this->createStub(ListingSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 42]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $route): string => '/'.$route);

        $view = (new ListingDetailViewBuilder($serializer, $urlGenerator))->showView($listing);

        self::assertSame(['id' => 42], $view['listing']);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
    }
}
