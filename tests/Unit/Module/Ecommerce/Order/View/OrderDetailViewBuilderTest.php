<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Order\View;

use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Dev\Audit\Serializer\AuditLogSerializer;
use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializerInterface;
use Aurora\Module\Ecommerce\Order\View\OrderDetailViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsOrderAndActivity(): void
    {
        $order = $this->createStub(OrderInterface::class);
        $order->method('getId')->willReturn(42);

        $orderSerializer = $this->createStub(OrderSerializerInterface::class);
        $orderSerializer->method('serialize')->willReturn(['id' => 42]);

        $auditRepo = $this->createStub(AuditLogRepository::class);
        $auditRepo->method('findPaginatedForEntity')->willReturn([
            'items' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 0,
        ]);

        $auditSerializer = $this->createStub(AuditLogSerializer::class);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $view = (new OrderDetailViewBuilder($orderSerializer, $auditRepo, $auditSerializer, $urlGenerator))->showView($order);

        self::assertSame(['id' => 42], $view['order']);
        self::assertSame([], $view['activity']);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('updateStatusPath', $view);
        self::assertArrayHasKey('refundPath', $view);
    }
}
