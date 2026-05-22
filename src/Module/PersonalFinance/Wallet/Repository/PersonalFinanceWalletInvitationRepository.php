<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitation;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceWalletInvitationInterface> */
class PersonalFinanceWalletInvitationRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceWalletInvitation::class, PersonalFinanceWalletInvitationInterface::class);
    }

    public function findOneByToken(string $token): ?PersonalFinanceWalletInvitationInterface
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findOneByWalletAndEmail(PersonalFinanceWalletInterface $wallet, string $email): ?PersonalFinanceWalletInvitationInterface
    {
        return $this->createQueryBuilder('i')
            ->where('i.wallet = :wallet')
            ->andWhere('LOWER(i.email) = LOWER(:email)')
            ->setParameter('wallet', $wallet)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<PersonalFinanceWalletInvitationInterface> */
    public function findPendingByWallet(PersonalFinanceWalletInterface $wallet): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.wallet = :wallet')
            ->andWhere('i.acceptedAt IS NULL')
            ->andWhere('i.declinedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('wallet', $wallet)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
