<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Entity;

use Aurora\Module\Ecommerce\Cart\Repository\CartRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\Table(name: 'core_ecommerce_carts')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_cart_session', columns: ['session_id'])]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_cart_user', columns: ['user_id'])]
class Cart extends AbstractCart
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_cart_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
