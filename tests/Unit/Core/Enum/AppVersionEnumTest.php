<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Enum;

use Aurora\Core\Enum\AppVersionEnum;
use PHPUnit\Framework\TestCase;

final class AppVersionEnumTest extends TestCase
{
    public function testDevCaseValue(): void
    {
        self::assertSame('dev', AppVersionEnum::Dev->value);
    }
}
