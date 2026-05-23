<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Aurora\Module\PersonalFinance\Budget\Service\PersonalFinanceMonthResetServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Dto\PersonalFinanceTransferInput;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Service\PersonalFinanceTransferServiceInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceMonthResetServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceMonthResetServiceInterface $resetService;
    private PersonalFinanceTransactionRepository $transactionRepository;
    private PersonalFinanceBudgetRepository $budgetRepository;
        private PersonalFinanceTransferServiceInterface $transferService;

    private function walletRepository(): PersonalFinanceWalletRepository
    {
        return $this->getService(PersonalFinanceWalletRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetService = $this->getService(PersonalFinanceMonthResetServiceInterface::class);
        $this->transactionRepository = $this->getService(PersonalFinanceTransactionRepository::class);
        $this->budgetRepository = $this->getService(PersonalFinanceBudgetRepository::class);
        $this->transferService = $this->getService(PersonalFinanceTransferServiceInterface::class);
    }

    public function testResetDeletesOnlyTransactionsOfTargetMonth(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');
        $cat = $this->createCategory($wallet, 'Food');

        // 3 tx in May, 2 tx in June (must survive)
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-05-02'), 'may-1');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-05-15'), 'may-2');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-05-31'), 'may-3');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '500.00', new DateTimeImmutable('2026-06-01'), 'june-keep-1');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '40.00', new DateTimeImmutable('2026-06-15'), 'june-keep-2');

        $report = $this->resetService->reset($wallet, new DateTimeImmutable('2026-05-15'), cascade: false, clearBudget: false);

        self::assertSame(3, $report->deletedTransactions);
        self::assertFalse($report->budgetCleared);
        self::assertSame(1, $report->monthsProcessed);

        $remaining = $this->transactionRepository->findByWallet($wallet);
        self::assertCount(2, $remaining);
        $descriptions = array_map(static fn ($t) => $t->getDescription(), $remaining);
        self::assertContains('june-keep-1', $descriptions);
        self::assertContains('june-keep-2', $descriptions);
    }

    public function testCascadeResetWipesEveryMonthUpToLatestDataPoint(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');
        $cat = $this->createCategory($wallet, 'Food');

        $now = new DateTimeImmutable('first day of this month');
        $twoMonthsAgo = $now->modify('-2 months');
        $oneMonthAgo = $now->modify('-1 month');
        $futureMonth = $now->modify('+1 month');

        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', $twoMonthsAgo->modify('+5 days'), 'past-1');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', $oneMonthAgo->modify('+5 days'), 'past-2');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', $now->modify('+5 days'), 'current');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '99.00', $futureMonth->modify('+5 days'), 'future-also-wiped');

        // Cascade ends at the latest data point on the wallet (futureMonth here)
        // — the +1-month tx is included, contrary to the older cap-at-today policy.
        $report = $this->resetService->reset($wallet, $twoMonthsAgo, cascade: true, clearBudget: false);

        self::assertSame(4, $report->deletedTransactions);
        self::assertSame(4, $report->monthsProcessed);

        $remaining = $this->transactionRepository->findByWallet($wallet);
        self::assertCount(0, $remaining);
    }

    public function testCascadeFromFutureMonthWipesItAndAnythingLater(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');
        $cat = $this->createCategory($wallet, 'Food');

        $futureMonth = new DateTimeImmutable('first day of this month')->modify('+2 months');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', $futureMonth->modify('+5 days'), 'future-tx');

        // cascade=true with only one data point at +2 → end = that month, single iteration
        $report = $this->resetService->reset($wallet, $futureMonth, cascade: true, clearBudget: false);

        self::assertSame(1, $report->deletedTransactions);
        self::assertSame(1, $report->monthsProcessed);
    }

    public function testCascadeWithNoFutureDataStopsAtCurrentMonth(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');
        $cat = $this->createCategory($wallet, 'Food');

        // Past-only history: 2 months ago + 1 month ago. Cascade from -2 → end
        // resolves to today's month even though no tx land in the current month
        // (`today` is always a candidate floor in resolveEndMonth).
        $now = new DateTimeImmutable('first day of this month');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', $now->modify('-2 months')->modify('+5 days'), 'past-1');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', $now->modify('-1 month')->modify('+5 days'), 'past-2');

        $report = $this->resetService->reset($wallet, $now->modify('-2 months'), cascade: true, clearBudget: false);

        self::assertSame(2, $report->deletedTransactions);
        self::assertSame(3, $report->monthsProcessed); // -2, -1, current (empty but still iterated)
    }

    public function testResetCleansBothTransferLegsAcrossWallets(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '1000.00');
        $to = $this->createWallet($user, 'To', '0.00');

        $transferId = $this->transferService->create($user, $from, $to, new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '120.00',
            date: new DateTimeImmutable('2026-05-10'),
        ));
        self::assertCount(2, $this->transactionRepository->findByTransferId($transferId), 'sanity: transfer creates 2 legs');

        // Reset May on $from — should also wipe the income leg on $to
        $report = $this->resetService->reset($from, new DateTimeImmutable('2026-05-15'), cascade: false, clearBudget: false);

        self::assertSame(2, $report->deletedTransactions);
        self::assertCount(0, $this->transactionRepository->findByTransferId($transferId));
        self::assertCount(0, $this->transactionRepository->findByWallet($from));
        self::assertCount(0, $this->transactionRepository->findByWallet($to));
    }

    public function testResetWithClearBudgetDeletesBudgetEntity(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');
        $cat = $this->createCategory($wallet, 'Food');

        $budget = new PersonalFinanceBudget();
        $budget->setUser($user)->setWallet($wallet)->setMonth(new DateTimeImmutable('2026-05-01'));
        $this->entityManager->persist($budget);

        $item = new PersonalFinanceBudgetItem();
        $item->setBudget($budget)->setSection(PersonalFinanceBudgetSectionEnum::Expenses)->setLabel('Food')->setPlannedAmount('400.00')->setCategory($cat)->setPosition(0);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $wallet = $this->walletRepository()->find($wallet->getId());

        $report = $this->resetService->reset($wallet, new DateTimeImmutable('2026-05-15'), cascade: false, clearBudget: true);

        self::assertTrue($report->budgetCleared);
        self::assertNull($this->budgetRepository->findByWalletAndMonth($wallet, new DateTimeImmutable('2026-05-01')));
    }

    public function testResetWithoutClearBudgetKeepsBudgetEntity(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');

        $budget = new PersonalFinanceBudget();
        $budget->setUser($user)->setWallet($wallet)->setMonth(new DateTimeImmutable('2026-05-01'));
        $this->entityManager->persist($budget);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $wallet = $this->walletRepository()->find($wallet->getId());

        $report = $this->resetService->reset($wallet, new DateTimeImmutable('2026-05-15'), cascade: false, clearBudget: false);

        self::assertFalse($report->budgetCleared);
        self::assertNotNull($this->budgetRepository->findByWalletAndMonth($wallet, new DateTimeImmutable('2026-05-01')));
    }
}
