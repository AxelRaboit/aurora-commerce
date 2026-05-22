<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMember;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletMemberManagerInterface::class)]
class PersonalFinanceWalletMemberManager implements PersonalFinanceWalletMemberManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(PersonalFinanceWalletInterface $wallet, CoreUserInterface $user, PersonalFinanceWalletRoleEnum $role): PersonalFinanceWalletMemberInterface
    {
        $member = $this->createMember();
        $member->setWallet($wallet);
        $member->setUser($user);
        $member->setRole($role);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->auditCreated($member);

        return $member;
    }

    public function delete(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditDeleted($member);

        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }

    protected function createMember(): PersonalFinanceWalletMemberInterface
    {
        return new PersonalFinanceWalletMember();
    }

    protected function auditCreated(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_member.created', 'PersonalFinanceWalletMember', $member->getId(), $this->auditPayload($member));
    }

    protected function auditDeleted(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_member.deleted', 'PersonalFinanceWalletMember', $member->getId(), $this->auditPayload($member));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceWalletMemberInterface $member): array
    {
        return [
            'walletId' => $member->getWallet()->getId(),
            'userId' => $member->getUser()->getId(),
            'role' => $member->getRole()->value,
        ];
    }
}
