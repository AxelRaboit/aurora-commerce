<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Product\View;

use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Aurora\Module\Erp\Product\Serializer\ProductSerializerInterface;
use Aurora\Module\Erp\Product\View\ProductDetailViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProductDetailViewBuilderTest extends TestCase
{
    public function testShowViewReturnsProductAndPaths(): void
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn(42);

        $productSerializer = $this->createStub(ProductSerializerInterface::class);
        $productSerializer->method('serialize')->willReturn(['id' => 42, 'name' => 'Widget']);

        $auditRepo = $this->createStub(AuditLogRepository::class);
        $auditRepo->method('findPaginatedForEntity')->willReturn([
            'items' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 0,
        ]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $view = (new ProductDetailViewBuilder($productSerializer, $auditRepo, $urlGenerator))->showView($product);

        self::assertSame(['id' => 42, 'name' => 'Widget'], $view['product']);
        self::assertArrayHasKey('activity', $view);
        self::assertArrayHasKey('backPath', $view);
        self::assertArrayHasKey('updatePath', $view);
        self::assertArrayHasKey('activityPath', $view);
    }
}
