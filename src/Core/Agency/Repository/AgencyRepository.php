<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Repository;

use Aurora\Core\Agency\Entity\Agency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agency>
 */
class AgencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agency::class);
    }

    /** @return Agency[] */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('agency')
            ->orderBy('agency.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
