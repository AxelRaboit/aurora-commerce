<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Dev\MountPoint\Entity\MountPoint;
use Aurora\Module\Dev\MountPoint\Entity\MountPointInterface;
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
