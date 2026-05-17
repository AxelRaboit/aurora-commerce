<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<AssistantMountPointInterface> */
class AssistantMountPointRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssistantMountPoint::class, AssistantMountPointInterface::class);
    }

    /** @return list<AssistantMountPointInterface> */
    public function findForUser(CoreUserInterface $user): array
    {
        return array_values($this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.name', Order::Ascending->value)
            ->getQuery()
            ->getResult());
    }

    /** @return list<AssistantMountPointInterface> */
    public function findActiveForUser(CoreUserInterface $user): array
    {
        return array_values($this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.active = true')
            ->setParameter('user', $user)
            ->orderBy('m.name', Order::Ascending->value)
            ->getQuery()
            ->getResult());
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?AssistantMountPointInterface
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
