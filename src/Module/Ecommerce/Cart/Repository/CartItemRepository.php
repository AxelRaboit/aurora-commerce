<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Repository;

use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CartItem> */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findOneByCartAndListing(Cart $cart, Listing $listing): ?CartItem
    {
        return $this->findOneBy(['cart' => $cart, 'listing' => $listing]);
    }
}
