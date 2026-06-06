<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Module\Ecommerce\Cart\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'core_ecommerce_cart_items')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_cart_item_listing', columns: ['cart_id', 'listing_id'])]
class CartItem extends AbstractCartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_cart_item_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
