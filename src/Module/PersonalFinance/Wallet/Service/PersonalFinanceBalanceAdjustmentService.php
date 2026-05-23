<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Category\Manager\PersonalFinanceCategoryManagerInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceBalanceAdjustmentInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBalanceAdjustmentServiceInterface::class)]
class PersonalFinanceBalanceAdjustmentService implements PersonalFinanceBalanceAdjustmentServiceInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryManagerInterface $categoryManager,
        protected readonly PersonalFinanceWalletBalanceServiceInterface $balanceService,
    ) {}

    public function adjust(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceBalanceAdjustmentInputInterface $input,
    ): PersonalFinanceTransactionInterface {
        $current = $this->balanceService->currentBalance($wallet);
        $diff = bcsub($input->getNewBalance(), $current, 2);

        if (0 === bccomp($diff, '0', 2)) {
            throw new DomainException('No adjustment required — new balance matches the current balance.');
        }

        $isCredit = 1 === bccomp($diff, '0', 2);
        $amount = $isCredit ? $diff : bcmul($diff, '-1', 2);
        $type = $isCredit ? PersonalFinanceTransactionTypeEnum::Income : PersonalFinanceTransactionTypeEnum::Expense;

        return $this->entityManager->wrapInTransaction(function () use ($user, $wallet, $input, $amount, $type, $diff): PersonalFinanceTransactionInterface {
            $category = $this->categoryManager->getOrCreateSystem(
                $user,
                $wallet,
                PersonalFinanceSystemCategoryKeyEnum::BalanceAdjustment,
                $this->balanceAdjustmentCategoryName(),
            );

            $tx = $this->createTransaction();
            $tx->setUser($user);
            $tx->setWallet($wallet);
            $tx->setCategory($category);
            $tx->setType($type);
            $tx->setAmount($amount);
            $tx->setDate($input->getDate());
            $tx->setDescription($input->getDescription() ?? $this->defaultDescription());

            $this->entityManager->persist($tx);
            $this->entityManager->flush();

            $this->auditCreated($wallet, $tx, $diff);

            return $tx;
        });
    }

    /**
     * Hook: instantiate the concrete transaction class. Override in a
     * client subclass to return your extended entity (mirrors
     * PersonalFinanceTransactionManager::createTransaction).
     */
    protected function createTransaction(): PersonalFinanceTransactionInterface
    {
        return new PersonalFinanceTransaction();
    }

    protected function balanceAdjustmentCategoryName(): string
    {
        return 'Balance adjustment';
    }

    protected function defaultDescription(): string
    {
        return 'Balance adjustment';
    }

    protected function auditCreated(PersonalFinanceWalletInterface $wallet, PersonalFinanceTransactionInterface $tx, string $diff): void
    {
        $this->auditLogger->log('personal_finance', 'balance.adjusted', 'PersonalFinanceWallet', $wallet->getId(), [
            'walletId' => $wallet->getId(),
            'transactionId' => $tx->getId(),
            'diff' => $diff,
            'date' => $tx->getDate()->format('Y-m-d'),
        ]);
    }
}
