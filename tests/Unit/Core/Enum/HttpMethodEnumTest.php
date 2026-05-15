<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Enum;

use Aurora\Core\Enum\HttpMethodEnum;
use PHPUnit\Framework\TestCase;

final class HttpMethodEnumTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('GET', HttpMethodEnum::Get->value);
        self::assertSame('POST', HttpMethodEnum::Post->value);
        self::assertSame('PUT', HttpMethodEnum::Put->value);
        self::assertSame('DELETE', HttpMethodEnum::Delete->value);
        self::assertSame('PATCH', HttpMethodEnum::Patch->value);
        self::assertSame('HEAD', HttpMethodEnum::Head->value);
        self::assertSame('OPTIONS', HttpMethodEnum::Options->value);
        self::assertCount(7, HttpMethodEnum::cases());
    }
}
