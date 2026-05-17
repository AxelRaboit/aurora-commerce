<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Agency\Repository;

use Aurora\Core\Platform\Agency\Entity\Agency;
use Aurora\Core\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<AgencyInterface>
 */
class AgencyRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agency::class, AgencyInterface::class);
    }

    /** @return AgencyInterface[] */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('agency')
            ->orderBy('agency.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
