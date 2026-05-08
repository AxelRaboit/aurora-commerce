<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Dto;

interface MenuItemInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MenuItemInputInterface;
}
