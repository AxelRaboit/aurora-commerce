<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Repository;

use Aurora\Core\MountPoint\Entity\MountPoint;
use Aurora\Core\MountPoint\Entity\MountPointInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<MountPoint>
 */
class MountPointRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MountPoint::class, MountPointInterface::class);
    }

    /** @return list<MountPoint> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('mp')
            ->orderBy('mp.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
