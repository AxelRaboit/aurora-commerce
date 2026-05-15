<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Menu\Enum;

use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use PHPUnit\Framework\TestCase;

final class MenuItemVisibilityEnumTest extends TestCase
{
    public function testLabelKey(): void
    {
        self::assertSame('backend.menus.visibilities.always', MenuItemVisibilityEnum::Always->labelKey());
        self::assertSame('backend.menus.visibilities.guests_only', MenuItemVisibilityEnum::GuestsOnly->labelKey());
        self::assertSame('backend.menus.visibilities.authenticated_only', MenuItemVisibilityEnum::AuthenticatedOnly->labelKey());
    }
}
