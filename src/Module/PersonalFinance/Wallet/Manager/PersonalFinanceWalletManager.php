<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWallet;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletManagerInterface::class)]
class PersonalFinanceWalletManager implements PersonalFinanceWalletManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceWalletMemberManagerInterface $personalFinanceWalletMemberManager,
    ) {}

    public function create(CoreUserInterface $owner, PersonalFinanceWalletInputInterface $input): PersonalFinanceWalletInterface
    {
        $wallet = $this->createWallet();
        $wallet->setOwner($owner);
        $this->applyInput($wallet, $input);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        $this->createOwnerMembership($wallet, $owner);

        $this->auditCreated($wallet);

        return $wallet;
    }

    public function update(PersonalFinanceWalletInterface $wallet, PersonalFinanceWalletInputInterface $input): void
    {
        $this->applyInput($wallet, $input);
        $this->entityManager->flush();

        $this->auditUpdated($wallet);
    }

    public function delete(PersonalFinanceWalletInterface $wallet): void
    {
        $this->auditDeleted($wallet);

        $this->entityManager->remove($wallet);
        $this->entityManager->flush();
    }

    protected function createWallet(): PersonalFinanceWalletInterface
    {
        return new PersonalFinanceWallet();
    }

    protected function createOwnerMembership(PersonalFinanceWalletInterface $wallet, CoreUserInterface $owner): PersonalFinanceWalletMemberInterface
    {
        return $this->personalFinanceWalletMemberManager->create($wallet, $owner, PersonalFinanceWalletRoleEnum::Owner);
    }

    protected function applyInput(PersonalFinanceWalletInterface $wallet, PersonalFinanceWalletInputInterface $input): void
    {
        $wallet->setName($input->getName());
        $wallet->setStartBalance($input->getStartBalance());
        $wallet->setMode($input->getMode());
        $wallet->setShowOnDashboard($input->isShowOnDashboard());
        $wallet->setPosition($input->getPosition());
    }

    protected function auditCreated(PersonalFinanceWalletInterface $wallet): void
    {
        $this->auditLogger->log('personal_finance', 'wallet.created', 'PersonalFinanceWallet', $wallet->getId(), $this->auditPayload($wallet));
    }

    protected function auditUpdated(PersonalFinanceWalletInterface $wallet): void
    {
        $this->auditLogger->log('personal_finance', 'wallet.updated', 'PersonalFinanceWallet', $wallet->getId(), $this->auditPayload($wallet));
    }

    protected function auditDeleted(PersonalFinanceWalletInterface $wallet): void
    {
        $this->auditLogger->log('personal_finance', 'wallet.deleted', 'PersonalFinanceWallet', $wallet->getId(), $this->auditPayload($wallet));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceWalletInterface $wallet): array
    {
        return [
            'name' => $wallet->getName(),
            'mode' => $wallet->getMode()->value,
            'startBalance' => $wallet->getStartBalance(),
        ];
    }
}
