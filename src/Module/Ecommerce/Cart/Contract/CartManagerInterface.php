<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Contract;

use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;

interface CartManagerInterface
{
    public function getCurrentCart(bool $createIfMissing = true): ?Cart;

    public function addItem(Listing $listing, int $quantity = 1): Cart;

    public function updateItemQuantity(Listing $listing, int $quantity): Cart;

    public function removeItem(Listing $listing): Cart;

    public function clear(): void;
}
