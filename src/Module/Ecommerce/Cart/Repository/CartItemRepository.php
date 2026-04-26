<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Cart\Repository;

use App\Module\Ecommerce\Cart\Entity\Cart;
use App\Module\Ecommerce\Cart\Entity\CartItem;
use App\Module\Ecommerce\Listing\Entity\Listing;
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
