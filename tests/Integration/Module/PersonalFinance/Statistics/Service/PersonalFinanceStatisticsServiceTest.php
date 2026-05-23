<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Statistics\Service;

use Aurora\Module\PersonalFinance\Statistics\Service\PersonalFinanceStatisticsServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

/**
 * Temporal analytics for the Statistics page. Validates the monthly
 * series boundaries, the period clamping, the per-category trend
 * shape, and the year-over-year comparison.
 */
final class PersonalFinanceStatisticsServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceStatisticsServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceStatisticsServiceInterface::class);
    }

    public function testSnapshotClampsUnknownPeriodToDefault(): void
    {
        $user = $this->createTestUser();

        $snap = $this->service->snapshot($user, 7, new DateTimeImmutable('2026-05-15'));

        // 7 isn't in the allowed [3,6,12] set → fallback to 6.
        self::assertSame(6, $snap['months']);
        self::assertCount(6, $snap['monthlyFlow']);
    }

    public function testMonthlyFlowReturnsOneEntryPerMonthOldestFirst(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $cat = $this->createCategory($wallet, 'Food');

        // Spread expenses across 3 months
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '100.00', new DateTimeImmutable('2026-03-10'));
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '200.00', new DateTimeImmutable('2026-04-10'));
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '300.00', new DateTimeImmutable('2026-05-10'));

        $snap = $this->service->snapshot($user, 3, new DateTimeImmutable('2026-05-15'));

        self::assertCount(3, $snap['monthlyFlow']);
        // Oldest first → March (i=0), April (i=1), May (i=2)
        self::assertSame('2026-03', $snap['monthlyFlow'][0]['month']);
        self::assertSame('100.00', $snap['monthlyFlow'][0]['expense']);
        self::assertSame('2026-04', $snap['monthlyFlow'][1]['month']);
        self::assertSame('200.00', $snap['monthlyFlow'][1]['expense']);
        self::assertSame('2026-05', $snap['monthlyFlow'][2]['month']);
        self::assertSame('300.00', $snap['monthlyFlow'][2]['income']);
        self::assertSame('300.00', $snap['monthlyFlow'][2]['net']);
    }

    public function testCategoryTrendReturnsTop5WithMonthlySeries(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $food = $this->createCategory($wallet, 'Food');
        $rent = $this->createCategory($wallet, 'Rent');

        $this->createTransaction($wallet, $food, PersonalFinanceTransactionTypeEnum::Expense, '50.00', new DateTimeImmutable('2026-04-10'));
        $this->createTransaction($wallet, $food, PersonalFinanceTransactionTypeEnum::Expense, '80.00', new DateTimeImmutable('2026-05-10'));
        $this->createTransaction($wallet, $rent, PersonalFinanceTransactionTypeEnum::Expense, '800.00', new DateTimeImmutable('2026-05-01'));

        $snap = $this->service->snapshot($user, 3, new DateTimeImmutable('2026-05-15'));

        self::assertCount(2, $snap['categoryTrend']);
        self::assertSame('Rent', $snap['categoryTrend'][0]['categoryName']);
        self::assertSame('800.00', $snap['categoryTrend'][0]['total']);
        self::assertCount(3, $snap['categoryTrend'][0]['series']);
        // Rent only has data in May
        self::assertSame('0.00', $snap['categoryTrend'][0]['series'][0]['expense']); // March
        self::assertSame('0.00', $snap['categoryTrend'][0]['series'][1]['expense']); // April
        self::assertSame('800.00', $snap['categoryTrend'][0]['series'][2]['expense']); // May
    }

    public function testYoyComparisonReadsSameMonthLastYear(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $cat = $this->createCategory($wallet, 'Food');

        // Last year same month
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '1000.00', new DateTimeImmutable('2025-05-10'));
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '600.00', new DateTimeImmutable('2025-05-15'));
        // This year same month
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '1200.00', new DateTimeImmutable('2026-05-10'));
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '500.00', new DateTimeImmutable('2026-05-15'));

        $snap = $this->service->snapshot($user, 6, new DateTimeImmutable('2026-05-20'));

        self::assertSame('2026-05', $snap['yoyComparison']['thisMonth']);
        self::assertSame('2025-05', $snap['yoyComparison']['lastYearMonth']);
        self::assertSame('1200.00', $snap['yoyComparison']['income']['current']);
        self::assertSame('1000.00', $snap['yoyComparison']['income']['previous']);
        self::assertSame(20.0, $snap['yoyComparison']['income']['deltaPercent']);
        self::assertSame('500.00', $snap['yoyComparison']['expense']['current']);
        self::assertSame('600.00', $snap['yoyComparison']['expense']['previous']);
        self::assertSame(-16.7, $snap['yoyComparison']['expense']['deltaPercent']);
    }

    public function testYoyDeltaPercentNullWhenPreviousIsZero(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $cat = $this->createCategory($wallet, 'Food');

        // Only this year — no data for May 2025
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '500.00', new DateTimeImmutable('2026-05-10'));

        $snap = $this->service->snapshot($user, 6, new DateTimeImmutable('2026-05-15'));

        self::assertNull($snap['yoyComparison']['income']['deltaPercent']);
        self::assertSame('500.00', $snap['yoyComparison']['income']['current']);
        self::assertSame('0.00', $snap['yoyComparison']['income']['previous']);
    }
}
