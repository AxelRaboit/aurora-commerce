<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceWalletBalanceServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceWalletBalanceServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceWalletBalanceServiceInterface::class);
    }

    public function testCurrentBalanceOnEmptyWalletEqualsStartBalance(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'Empty', '500.00');

        self::assertSame('500.00', $this->service->currentBalance($wallet));
    }

    public function testCurrentBalanceAddsIncomeAndSubtractsExpense(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-03-10'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-12'));

        // 100 + 50 - 20 = 130.00
        self::assertSame('130.00', $this->service->currentBalance($wallet));
    }

    public function testMonthlyBalanceReturnsRunningBalanceAtMonthEnd(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        // February: +30
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '30.00', new DateTimeImmutable('2026-02-15'));

        // March: +50, -20 → net +30 → running = 100 + 30 (Feb) + 30 (Mar) = 160
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-03-05'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-20'));

        self::assertSame('160.00', $this->service->monthlyBalance($wallet, new DateTimeImmutable('2026-03-15')));
    }

    public function testRollingStartBalanceIsBalanceBeforeMonthStart(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        // January: +200
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '200.00', new DateTimeImmutable('2026-01-10'));
        // February: +50 (should NOT be included in March's rollingStart)
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-02-20'));
        // March activity (should NOT be included)
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '999.99', new DateTimeImmutable('2026-03-05'));

        // 100 (start) + 200 (Jan) + 50 (Feb) = 350.00 — March entries excluded
        self::assertSame('350.00', $this->service->rollingStartBalance($wallet, new DateTimeImmutable('2026-03-15')));
    }

    public function testRollingStartBalanceForFirstMonthEqualsStartBalance(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '42.50');

        // Only entries in the same month — should be ignored by rollingStart
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '999.99', new DateTimeImmutable('2026-03-10'));

        self::assertSame('42.50', $this->service->rollingStartBalance($wallet, new DateTimeImmutable('2026-03-15')));
    }

    public function testSnapshotReturnsAllThreeFiguresAsStringsWithTwoDecimals(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        // Past month: +20
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '20.00', new DateTimeImmutable('2026-01-15'));
        // Current month (March 2026): +50 -10 = net +40
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-03-05'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-03-15'));

        $snapshot = $this->service->snapshot($wallet, new DateTimeImmutable('2026-03-15'));

        self::assertArrayHasKey('current', $snapshot);
        self::assertArrayHasKey('month', $snapshot);
        self::assertArrayHasKey('rollingStart', $snapshot);

        // rollingStart = 100 + 20 = 120
        self::assertSame('120.00', $snapshot['rollingStart']);
        // current = 100 + 20 + 50 - 10 = 160
        self::assertSame('160.00', $snapshot['current']);
        // month = rollingStart + month net = 120 + 40 = 160
        self::assertSame('160.00', $snapshot['month']);
    }

    public function testBalanceAcrossMultipleMonthsRemainsConsistent(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '1000.00', new DateTimeImmutable('2026-01-01'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '300.00', new DateTimeImmutable('2026-02-01'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '200.00', new DateTimeImmutable('2026-03-01'));

        // current = 1000 - 300 - 200 = 500
        self::assertSame('500.00', $this->service->currentBalance($wallet));
        // March rollingStart = 1000 - 300 = 700
        self::assertSame('700.00', $this->service->rollingStartBalance($wallet, new DateTimeImmutable('2026-03-15')));
        // March monthly = 700 - 200 = 500
        self::assertSame('500.00', $this->service->monthlyBalance($wallet, new DateTimeImmutable('2026-03-15')));
    }
}
