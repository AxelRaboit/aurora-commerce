<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Repository;

use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OrderLine> */
class OrderLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderLine::class);
    }
}
