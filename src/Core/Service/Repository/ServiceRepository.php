<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Repository;

use Aurora\Core\Service\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /** @return Service[] */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('service')
            ->orderBy('service.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
