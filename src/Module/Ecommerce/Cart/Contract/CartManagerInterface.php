<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Contract;

use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;

interface CartManagerInterface
{
    public function getCurrentCart(bool $createIfMissing = true): ?CartInterface;

    public function addItem(ListingInterface $listing, int $quantity = 1): CartInterface;

    public function updateItemQuantity(ListingInterface $listing, int $quantity): CartInterface;

    public function removeItem(ListingInterface $listing): CartInterface;

    public function clear(): void;
}
