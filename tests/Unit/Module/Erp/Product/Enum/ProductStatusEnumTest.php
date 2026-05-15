<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Product\Enum;

use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use PHPUnit\Framework\TestCase;

final class ProductStatusEnumTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('draft', ProductStatusEnum::Draft->value);
        self::assertSame('active', ProductStatusEnum::Active->value);
        self::assertSame('archived', ProductStatusEnum::Archived->value);
        self::assertCount(3, ProductStatusEnum::cases());
    }
}
