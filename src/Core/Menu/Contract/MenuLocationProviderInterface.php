<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Contract;

use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;

interface MenuLocationProviderInterface
{
    /**
     * @return array<string, array{
     *     name: string,
     *     description: ?string,
     *     defaultItems: array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}>,
     * }>
     */
    public function getMenuLocations(): array;
}
