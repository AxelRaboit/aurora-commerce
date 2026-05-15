<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Product\Enum;

use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use PHPUnit\Framework\TestCase;

final class ProductTypeEnumTest extends TestCase
{
    public function testRequiresShippingOnlyForPhysical(): void
    {
        self::assertTrue(ProductTypeEnum::Physical->requiresShipping());
        self::assertFalse(ProductTypeEnum::Digital->requiresShipping());
        self::assertFalse(ProductTypeEnum::Service->requiresShipping());
    }

    public function testTracksStockByDefaultOnlyForPhysical(): void
    {
        self::assertTrue(ProductTypeEnum::Physical->tracksStockByDefault());
        self::assertFalse(ProductTypeEnum::Digital->tracksStockByDefault());
        self::assertFalse(ProductTypeEnum::Service->tracksStockByDefault());
    }

    public function testValuesReturnsAllCases(): void
    {
        self::assertSame(['physical', 'digital', 'service'], ProductTypeEnum::values());
    }
}
