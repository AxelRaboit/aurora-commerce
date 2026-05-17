<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<VaultEntryInterface> */
class VaultEntryRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VaultEntry::class, VaultEntryInterface::class);
    }

    /**
     * Returns all entries for a user, with folder data JOIN-loaded to avoid N+1.
     *
     * @return list<VaultEntryInterface>
     */
    public function findByUserWithFolder(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.folder', 'f')
            ->addSelect('f')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.isFavorite', Order::Descending->value)
            ->addOrderBy('e.title', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<VaultEntryInterface>
     */
    public function findByUserAndFolder(CoreUserInterface $user, int $folderId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.folder', 'f')
            ->addSelect('f')
            ->where('e.user = :user')
            ->andWhere('f.id = :folderId')
            ->setParameter('user', $user)
            ->setParameter('folderId', $folderId)
            ->orderBy('e.isFavorite', Order::Descending->value)
            ->addOrderBy('e.title', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<VaultEntryInterface>
     */
    public function findFavoritesByUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.folder', 'f')
            ->addSelect('f')
            ->where('e.user = :user')
            ->andWhere('e.isFavorite = true')
            ->setParameter('user', $user)
            ->orderBy('e.title', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?VaultEntryInterface
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
