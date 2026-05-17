<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<VaultFolderInterface> */
class VaultFolderRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VaultFolder::class, VaultFolderInterface::class);
    }

    /**
     * Returns ALL folders for a user as a flat list (frontend builds the tree).
     *
     * @return list<VaultFolderInterface>
     */
    public function findAllByUserOrdered(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.position', Order::Ascending->value)
            ->addOrderBy('f.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?VaultFolderInterface
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countNextPositionForParent(CoreUserInterface $user, ?VaultFolderInterface $parent): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->setParameter('user', $user);

        if (!$parent instanceof VaultFolderInterface) {
            $qb->andWhere('f.parent IS NULL');
        } else {
            $qb->andWhere('f.parent = :parent')->setParameter('parent', $parent);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
