<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Entity;

use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'core_erp_products')]
#[ORM\UniqueConstraint(name: 'uniq_erp_product_reference', columns: ['reference'])]
class Product extends AbstractProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_product_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
