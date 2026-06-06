<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<CartItemInterface> */
class CartItemRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class, CartItemInterface::class);
    }

    public function findOneByCartAndListing(CartInterface $cart, ListingInterface $listing): ?CartItemInterface
    {
        return $this->findOneBy(['cart' => $cart, 'listing' => $listing]);
    }
}
