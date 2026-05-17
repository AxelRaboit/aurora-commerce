<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Configuration\Setting\Enum;

use Aurora\Module\Configuration\Setting\Enum\SettingErrorCodeEnum;
use PHPUnit\Framework\TestCase;

final class SettingErrorCodeEnumTest extends TestCase
{
    public function testCascadeViolationValue(): void
    {
        self::assertSame('cascade_violation', SettingErrorCodeEnum::CascadeViolation->value);
    }
}
