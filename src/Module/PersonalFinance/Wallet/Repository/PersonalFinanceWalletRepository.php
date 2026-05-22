<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWallet;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceWalletInterface> */
class PersonalFinanceWalletRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceWallet::class, PersonalFinanceWalletInterface::class);
    }

    /**
     * Returns all wallets owned by a user, ordered by position then name.
     *
     * @return list<PersonalFinanceWalletInterface>
     */
    public function findByOwner(CoreUserInterface $owner): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('w.position', Order::Ascending->value)
            ->addOrderBy('w.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findOneByOwnerAndId(CoreUserInterface $owner, int $id): ?PersonalFinanceWalletInterface
    {
        return $this->createQueryBuilder('w')
            ->where('w.owner = :owner')
            ->andWhere('w.id = :id')
            ->setParameter('owner', $owner)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns all wallets the user can access (any role: Owner, Editor, Viewer).
     *
     * @return list<PersonalFinanceWalletInterface>
     */
    public function findAccessibleByUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.members', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.position', Order::Ascending->value)
            ->addOrderBy('w.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
