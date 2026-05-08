<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'core_ecommerce_orders')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_order_number', columns: ['number'])]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_order_token', columns: ['token'])]
class Order extends AbstractOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_order_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
