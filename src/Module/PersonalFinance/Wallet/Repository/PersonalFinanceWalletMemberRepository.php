<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMember;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceWalletMemberInterface> */
class PersonalFinanceWalletMemberRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceWalletMember::class, PersonalFinanceWalletMemberInterface::class);
    }

    public function findOneByWalletAndUser(PersonalFinanceWalletInterface $wallet, CoreUserInterface $user): ?PersonalFinanceWalletMemberInterface
    {
        return $this->createQueryBuilder('m')
            ->where('m.wallet = :wallet')
            ->andWhere('m.user = :user')
            ->setParameter('wallet', $wallet)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<PersonalFinanceWalletMemberInterface> */
    public function findByWallet(PersonalFinanceWalletInterface $wallet): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('m.role', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOwnerOfWallet(PersonalFinanceWalletInterface $wallet): ?PersonalFinanceWalletMemberInterface
    {
        return $this->createQueryBuilder('m')
            ->where('m.wallet = :wallet')
            ->andWhere('m.role = :role')
            ->setParameter('wallet', $wallet)
            ->setParameter('role', PersonalFinanceWalletRoleEnum::Owner)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
