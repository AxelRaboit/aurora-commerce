<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Serializer;

use Aurora\Core\Menu\Entity\MenuItemInterface;

interface MenuItemSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MenuItemInterface $item, array $postCache = [], array $termCache = [], array $postTypeCache = []): array;

    /** @return array<string, mixed> */
    public function preloadTargets(iterable $items): array;
}
