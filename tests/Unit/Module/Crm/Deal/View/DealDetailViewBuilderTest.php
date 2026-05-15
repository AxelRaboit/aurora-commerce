<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Deal\View;

use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Serializer\DealSerializerInterface;
use Aurora\Module\Crm\Deal\View\DealDetailViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DealDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsSerializedDealAndPaths(): void
    {
        $deal = $this->createStub(DealInterface::class);
        $deal->method('getId')->willReturn(7);

        $serializer = $this->createStub(DealSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 7]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $route): string => '/'.$route);

        $view = (new DealDetailViewBuilder($serializer, $urlGenerator))->showView($deal);

        self::assertSame(['id' => 7], $view['deal']);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('deletePath', $view);
        self::assertArrayHasKey('updateStagePath', $view);
    }
}
