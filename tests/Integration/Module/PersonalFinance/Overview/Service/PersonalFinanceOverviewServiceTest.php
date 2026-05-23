<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Overview\Service;

use Aurora\Module\PersonalFinance\Overview\Service\PersonalFinanceOverviewServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

/**
 * Cross-wallet aggregations for the Overview page. The service is the
 * sibling of PersonalFinanceDashboardService but framed differently :
 * Overview ignores `showOnDashboard` pinning and aggregates over
 * every wallet the user can access.
 */
final class PersonalFinanceOverviewServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceOverviewServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceOverviewServiceInterface::class);
    }

    public function testSnapshotForEmptyAccountReturnsZeroes(): void
    {
        $user = $this->createTestUser();

        $snapshot = $this->service->snapshot($user, new DateTimeImmutable('2026-05-15'));

        self::assertSame(0, $snapshot['totals']['walletCount']);
        self::assertSame('0.00', $snapshot['totals']['totalBalance']);
        self::assertSame('0.00', $snapshot['totals']['monthIncome']);
        self::assertSame('0.00', $snapshot['totals']['monthExpense']);
        self::assertSame('0.00', $snapshot['totals']['monthNet']);
        self::assertSame([], $snapshot['walletsBreakdown']);
        self::assertSame([], $snapshot['categoryBreakdown']);
        self::assertSame([], $snapshot['recentTransactions']);
    }

    public function testSnapshotAggregatesBalancesAndMonthFlowAcrossWallets(): void
    {
        $user = $this->createTestUser();
        $today = new DateTimeImmutable('2026-05-15');

        $w1 = $this->createWallet($user, 'Checking', '1000.00');
        $w2 = $this->createWallet($user, 'Savings', '5000.00');

        $cat = $this->createCategory($w1, 'Food');
        $this->createTransaction($w1, $cat, PersonalFinanceTransactionTypeEnum::Income, '500.00', new DateTimeImmutable('2026-05-10'), 'salary');
        $this->createTransaction($w1, $cat, PersonalFinanceTransactionTypeEnum::Expense, '120.00', new DateTimeImmutable('2026-05-12'), 'groceries');
        $this->createTransaction($w2, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-05-08'), 'interest');

        $snapshot = $this->service->snapshot($user, $today);

        self::assertSame(2, $snapshot['totals']['walletCount']);
        // Total balance = 1000 + 500 - 120 + 5000 + 50 = 6430
        self::assertSame('6430.00', $snapshot['totals']['totalBalance']);
        self::assertSame('550.00', $snapshot['totals']['monthIncome']);
        self::assertSame('120.00', $snapshot['totals']['monthExpense']);
        self::assertSame('430.00', $snapshot['totals']['monthNet']);
    }

    public function testWalletsBreakdownSortedByBalanceDesc(): void
    {
        $user = $this->createTestUser();
        $w1 = $this->createWallet($user, 'Small', '100.00');
        $w2 = $this->createWallet($user, 'Big', '9000.00');
        $w3 = $this->createWallet($user, 'Medium', '2000.00');

        $snapshot = $this->service->snapshot($user, new DateTimeImmutable('2026-05-15'));

        $names = array_map(static fn (array $row): string => $row['name'], $snapshot['walletsBreakdown']);
        self::assertSame(['Big', 'Medium', 'Small'], $names);
        self::assertSame(81, $snapshot['walletsBreakdown'][0]['share']); // 9000 / 11100
    }

    public function testCategoryBreakdownAggregatesAcrossWalletsAndExcludesTransferLegs(): void
    {
        $user = $this->createTestUser();
        $today = new DateTimeImmutable('2026-05-15');

        $w1 = $this->createWallet($user, 'A');
        $w2 = $this->createWallet($user, 'B');
        $catFood1 = $this->createCategory($w1, 'Food');
        $catFood2 = $this->createCategory($w2, 'Food');
        $catRent = $this->createCategory($w1, 'Rent');

        $this->createTransaction($w1, $catFood1, PersonalFinanceTransactionTypeEnum::Expense, '60.00', new DateTimeImmutable('2026-05-05'));
        $this->createTransaction($w2, $catFood2, PersonalFinanceTransactionTypeEnum::Expense, '40.00', new DateTimeImmutable('2026-05-08'));
        $this->createTransaction($w1, $catRent, PersonalFinanceTransactionTypeEnum::Expense, '800.00', new DateTimeImmutable('2026-05-01'));

        // Transfer leg with a regular category — must NOT contribute.
        $this->createTransaction($w1, $catFood1, PersonalFinanceTransactionTypeEnum::Expense, '999.00', new DateTimeImmutable('2026-05-10'), transferId: 'tid');

        $snapshot = $this->service->snapshot($user, $today);

        // Food sums to 60+40 = 100 across wallets ; Rent 800. Sorted desc.
        self::assertCount(2, $snapshot['categoryBreakdown']);
        self::assertSame('Rent', $snapshot['categoryBreakdown'][0]['categoryName']);
        self::assertSame('800.00', $snapshot['categoryBreakdown'][0]['total']);
        self::assertSame('Food', $snapshot['categoryBreakdown'][1]['categoryName']);
        self::assertSame('100.00', $snapshot['categoryBreakdown'][1]['total']);
    }
}
