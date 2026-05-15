<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Module\Enum;

use Aurora\Core\Module\Enum\ModuleToggleTypeEnum;
use PHPUnit\Framework\TestCase;

final class ModuleToggleTypeEnumTest extends TestCase
{
    public function testFromKeyDetectsFrontendSuffix(): void
    {
        self::assertSame(ModuleToggleTypeEnum::Frontend, ModuleToggleTypeEnum::fromKey('backend_editorial_frontend'));
        self::assertSame(ModuleToggleTypeEnum::Frontend, ModuleToggleTypeEnum::fromKey('backend_photo_frontend'));
    }

    public function testFromKeyDefaultsToBackend(): void
    {
        self::assertSame(ModuleToggleTypeEnum::Backend, ModuleToggleTypeEnum::fromKey('backend_editorial'));
        self::assertSame(ModuleToggleTypeEnum::Backend, ModuleToggleTypeEnum::fromKey('frontend_root'));
        self::assertSame(ModuleToggleTypeEnum::Backend, ModuleToggleTypeEnum::fromKey('anything_else'));
    }
}
