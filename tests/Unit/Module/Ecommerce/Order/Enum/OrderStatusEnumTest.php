<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Order\Enum;

use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use PHPUnit\Framework\TestCase;

final class OrderStatusEnumTest extends TestCase
{
    public function testAllCasesHaveExpectedValues(): void
    {
        self::assertSame('pending', OrderStatusEnum::Pending->value);
        self::assertSame('paid', OrderStatusEnum::Paid->value);
        self::assertSame('shipped', OrderStatusEnum::Shipped->value);
        self::assertSame('delivered', OrderStatusEnum::Delivered->value);
        self::assertSame('cancelled', OrderStatusEnum::Cancelled->value);
        self::assertSame('refunded', OrderStatusEnum::Refunded->value);
    }

    public function testCasesContainsAllExpectedStates(): void
    {
        $cases = OrderStatusEnum::cases();

        self::assertCount(6, $cases);
    }
}
