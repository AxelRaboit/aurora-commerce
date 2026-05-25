<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Menu\Enum;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use PHPUnit\Framework\TestCase;

final class MenuItemTargetTypeEnumTest extends TestCase
{
    public function testLabelKey(): void
    {
        self::assertSame('backend.menus.target_types.post', MenuItemTargetTypeEnum::Post->labelKey());
        self::assertSame('backend.menus.target_types.term', MenuItemTargetTypeEnum::Term->labelKey());
        self::assertSame('backend.menus.target_types.frontend_login', MenuItemTargetTypeEnum::FrontLogin->labelKey());
    }

    public function testRequiresTargetIdOnlyForPostTermArchive(): void
    {
        self::assertTrue(MenuItemTargetTypeEnum::Post->requiresTargetId());
        self::assertTrue(MenuItemTargetTypeEnum::Term->requiresTargetId());
        self::assertTrue(MenuItemTargetTypeEnum::PostTypeArchive->requiresTargetId());

        self::assertFalse(MenuItemTargetTypeEnum::Home->requiresTargetId());
        self::assertFalse(MenuItemTargetTypeEnum::CustomUrl->requiresTargetId());
        self::assertFalse(MenuItemTargetTypeEnum::FrontLogin->requiresTargetId());
    }

    public function testRequiresCustomUrlOnlyForCustomUrl(): void
    {
        self::assertTrue(MenuItemTargetTypeEnum::CustomUrl->requiresCustomUrl());
        self::assertFalse(MenuItemTargetTypeEnum::Post->requiresCustomUrl());
        self::assertFalse(MenuItemTargetTypeEnum::Home->requiresCustomUrl());
    }
}
