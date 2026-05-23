<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Transfer\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Category\Manager\PersonalFinanceCategoryManagerInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Dto\PersonalFinanceTransferInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(PersonalFinanceTransferServiceInterface::class)]
class PersonalFinanceTransferService implements PersonalFinanceTransferServiceInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryManagerInterface $categoryManager,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
    ) {}

    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $fromWallet,
        PersonalFinanceWalletInterface $toWallet,
        PersonalFinanceTransferInputInterface $input,
    ): string {
        if ($fromWallet->getId() === $toWallet->getId()) {
            throw new DomainException('Transfer source and target wallets must differ.');
        }

        $transferId = Uuid::v7()->toRfc4122();

        $this->entityManager->wrapInTransaction(function () use ($user, $fromWallet, $toWallet, $input, $transferId): void {
            $expenseCategory = $this->categoryManager->getOrCreateSystem(
                $user,
                $fromWallet,
                PersonalFinanceSystemCategoryKeyEnum::transferExpenseKey((int) $toWallet->getId()),
                $this->transferExpenseName($fromWallet, $toWallet),
            );

            $incomeCategory = $this->categoryManager->getOrCreateSystem(
                $user,
                $toWallet,
                PersonalFinanceSystemCategoryKeyEnum::TransferIncome,
                $this->transferIncomeName($fromWallet, $toWallet),
            );

            $description = $input->getDescription() ?? $this->defaultDescription($fromWallet, $toWallet);

            $expense = $this->buildSide(
                $user,
                $fromWallet,
                $expenseCategory,
                PersonalFinanceTransactionTypeEnum::Expense,
                $input->getAmount(),
                $input->getDate(),
                $description,
                $transferId,
            );

            $income = $this->buildSide(
                $user,
                $toWallet,
                $incomeCategory,
                PersonalFinanceTransactionTypeEnum::Income,
                $input->getAmount(),
                $input->getDate(),
                $description,
                $transferId,
            );

            $this->entityManager->persist($expense);
            $this->entityManager->persist($income);
            $this->entityManager->flush();

            $this->auditCreated($transferId, $expense, $income);
        });

        return $transferId;
    }

    public function update(string $transferId, PersonalFinanceTransferInputInterface $input): void
    {
        [$expense, $income] = $this->loadPair($transferId);

        $this->entityManager->wrapInTransaction(function () use ($expense, $income, $input, $transferId): void {
            $description = $input->getDescription() ?? $this->defaultDescription($expense->getWallet(), $income->getWallet());

            foreach ([$expense, $income] as $side) {
                $side->setAmount($input->getAmount());
                $side->setDate($input->getDate());
                $side->setDescription($description);
            }

            $this->entityManager->flush();

            $this->auditUpdated($transferId, $expense, $income);
        });
    }

    public function delete(string $transferId): void
    {
        [$expense, $income] = $this->loadPair($transferId);

        $this->entityManager->wrapInTransaction(function () use ($expense, $income, $transferId): void {
            $this->auditDeleted($transferId, $expense, $income);

            $this->entityManager->remove($expense);
            $this->entityManager->remove($income);
            $this->entityManager->flush();
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

    protected function transferExpenseName(PersonalFinanceWalletInterface $fromWallet, PersonalFinanceWalletInterface $toWallet): string
    {
        return sprintf('Transfer to %s', $toWallet->getName());
    }

    protected function transferIncomeName(PersonalFinanceWalletInterface $fromWallet, PersonalFinanceWalletInterface $toWallet): string
    {
        return sprintf('Transfer from %s', $fromWallet->getName());
    }

    protected function defaultDescription(PersonalFinanceWalletInterface $fromWallet, PersonalFinanceWalletInterface $toWallet): string
    {
        return sprintf('Transfer from %s to %s', $fromWallet->getName(), $toWallet->getName());
    }

    /**
     * @return array{0: PersonalFinanceTransactionInterface, 1: PersonalFinanceTransactionInterface}
     */
    protected function loadPair(string $transferId): array
    {
        $transactions = $this->transactionRepository->findByTransferId($transferId);
        if (2 !== count($transactions)) {
            throw new DomainException(sprintf('Transfer %s is not in a valid 2-leg state (found %d).', $transferId, count($transactions)));
        }

        $expense = null;
        $income = null;
        foreach ($transactions as $tx) {
            if (PersonalFinanceTransactionTypeEnum::Expense === $tx->getType()) {
                $expense = $tx;
            } elseif (PersonalFinanceTransactionTypeEnum::Income === $tx->getType()) {
                $income = $tx;
            }
        }

        if (null === $expense || null === $income) {
            throw new DomainException(sprintf('Transfer %s legs are not paired as Expense+Income.', $transferId));
        }

        return [$expense, $income];
    }

    protected function buildSide(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceCategoryInterface $category,
        PersonalFinanceTransactionTypeEnum $type,
        string $amount,
        \DateTimeImmutable $date,
        ?string $description,
        string $transferId,
    ): PersonalFinanceTransactionInterface {
        $tx = $this->createTransaction();
        $tx->setUser($user);
        $tx->setWallet($wallet);
        $tx->setCategory($category);
        $tx->setType($type);
        $tx->setAmount($amount);
        $tx->setDate($date);
        $tx->setDescription($description);
        $tx->setTransferId($transferId);

        return $tx;
    }

    protected function auditCreated(string $transferId, PersonalFinanceTransactionInterface $expense, PersonalFinanceTransactionInterface $income): void
    {
        $this->auditLogger->log('personal_finance', 'transfer.created', 'PersonalFinanceTransfer', null, $this->auditPayload($transferId, $expense, $income));
    }

    protected function auditUpdated(string $transferId, PersonalFinanceTransactionInterface $expense, PersonalFinanceTransactionInterface $income): void
    {
        $this->auditLogger->log('personal_finance', 'transfer.updated', 'PersonalFinanceTransfer', null, $this->auditPayload($transferId, $expense, $income));
    }

    protected function auditDeleted(string $transferId, PersonalFinanceTransactionInterface $expense, PersonalFinanceTransactionInterface $income): void
    {
        $this->auditLogger->log('personal_finance', 'transfer.deleted', 'PersonalFinanceTransfer', null, $this->auditPayload($transferId, $expense, $income));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(string $transferId, PersonalFinanceTransactionInterface $expense, PersonalFinanceTransactionInterface $income): array
    {
        return [
            'transferId' => $transferId,
            'fromWalletId' => $expense->getWallet()->getId(),
            'toWalletId' => $income->getWallet()->getId(),
            'amount' => $expense->getAmount(),
            'date' => $expense->getDate()->format('Y-m-d'),
            'expenseTransactionId' => $expense->getId(),
            'incomeTransactionId' => $income->getId(),
        ];
    }
}
