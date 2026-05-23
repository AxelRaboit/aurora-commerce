<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletMemberInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMember;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletMemberManagerInterface::class)]
class PersonalFinanceWalletMemberManager implements PersonalFinanceWalletMemberManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(
        PersonalFinanceWalletInterface $wallet,
        CoreUserInterface $user,
        PersonalFinanceWalletMemberInputInterface $input,
    ): PersonalFinanceWalletMemberInterface {
        $member = $this->createMember();
        $member->setWallet($wallet);
        $member->setUser($user);
        $this->applyInput($member, $input);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->auditCreated($member);

        return $member;
    }

    public function update(
        PersonalFinanceWalletMemberInterface $member,
        PersonalFinanceWalletMemberInputInterface $input,
    ): void {
        // Owner role is managed by the dedicated transferOwnership flow —
        // any attempt to add it or remove it via the regular update path
        // is rejected up-front (rather than from applyInput) so the guard
        // is visible at the call site.
        if (PersonalFinanceWalletRoleEnum::Owner === $member->getRole()) {
            throw new DomainException('Cannot change the Owner role directly. Use transferOwnership instead.');
        }
        if (PersonalFinanceWalletRoleEnum::Owner === $input->getRole()) {
            throw new DomainException('Cannot promote to Owner via update. Use transferOwnership instead.');
        }

        $this->applyInput($member, $input);
        $this->entityManager->flush();

        $this->auditUpdated($member);
    }

    public function delete(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditDeleted($member);

        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }

    public function removeMember(PersonalFinanceWalletMemberInterface $member): void
    {
        if (PersonalFinanceWalletRoleEnum::Owner === $member->getRole()) {
            throw new DomainException('Cannot remove the Owner. Transfer ownership before removing.');
        }

        $this->delete($member);
    }

    protected function applyInput(
        PersonalFinanceWalletMemberInterface $member,
        PersonalFinanceWalletMemberInputInterface $input,
    ): void {
        $member->setRole($input->getRole());
    }

    protected function createMember(): PersonalFinanceWalletMemberInterface
    {
        return new PersonalFinanceWalletMember();
    }

    protected function auditCreated(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_member.created', 'PersonalFinanceWalletMember', $member->getId(), $this->auditPayload($member));
    }

    protected function auditUpdated(PersonalFinanceWalletMemberInterface $member): void
    {
        $this->auditLogger->log('personal_finance', 'wallet_member.updated', 'PersonalFinanceWalletMember', $member->getId(), $this->auditPayload($member));
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
