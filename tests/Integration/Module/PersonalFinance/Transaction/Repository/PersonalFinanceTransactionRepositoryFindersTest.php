<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Transaction\Repository;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceTransactionRepositoryFindersTest extends PersonalFinanceTestCase
{
    private PersonalFinanceTransactionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(PersonalFinanceTransactionRepository::class);
    }

    public function testFindPaginatedByCategoryAndMonthFiltersByMonthBoundary(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        // February (excluded)
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-02-28'), 'feb');
        // March (included)
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-01'), 'mar1');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-03-31'), 'mar2');
        // April (excluded)
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '40.00', new DateTimeImmutable('2026-04-01'), 'apr');

        $result = $this->repository->findPaginatedByCategoryAndMonth($cat, new DateTimeImmutable('2026-03-15'), 1, 50);

        self::assertSame(2, $result['total']);
        $descriptions = array_map(static fn ($t) => $t->getDescription(), $result['items']);
        self::assertContains('mar1', $descriptions);
        self::assertContains('mar2', $descriptions);
        self::assertNotContains('feb', $descriptions);
        self::assertNotContains('apr', $descriptions);
    }

    public function testFindPaginatedByCategoryAndMonthIsOrderedDescByDateThenId(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-03-01'), 'old');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-15'), 'middle');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-03-31'), 'new');

        $result = $this->repository->findPaginatedByCategoryAndMonth($cat, new DateTimeImmutable('2026-03-15'), 1, 50);

        $descriptions = array_map(static fn ($t) => $t->getDescription(), $result['items']);
        self::assertSame(['new', 'middle', 'old'], $descriptions);
    }

    public function testFindPaginatedByCategoryAndMonthSearchFiltersDescriptionCaseInsensitively(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-03-05'), 'Coffee shop');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-10'), 'Restaurant');
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-03-15'), 'Local CoFFee');

        $result = $this->repository->findPaginatedByCategoryAndMonth($cat, new DateTimeImmutable('2026-03-15'), 1, 50, 'coffee');

        self::assertSame(2, $result['total']);
    }

    public function testFindPaginatedByCategoryAndMonthPaginationMath(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        for ($i = 1; $i <= 25; ++$i) {
            $this->createTransaction(
                $wallet,
                $cat,
                PersonalFinanceTransactionTypeEnum::Expense,
                '1.00',
                new DateTimeImmutable(sprintf('2026-03-%02d', min($i, 28))),
                'tx-'.$i,
            );
        }

        $page1 = $this->repository->findPaginatedByCategoryAndMonth($cat, new DateTimeImmutable('2026-03-15'), 1, 10);
        self::assertSame(25, $page1['total']);
        self::assertSame(3, $page1['totalPages']);
        self::assertCount(10, $page1['items']);

        $page3 = $this->repository->findPaginatedByCategoryAndMonth($cat, new DateTimeImmutable('2026-03-15'), 3, 10);
        self::assertCount(5, $page3['items']);
    }

    public function testNetFlowReturnsIncomeMinusExpenseAsBcmathString(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '100.50', new DateTimeImmutable('2026-03-05'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '200.00', new DateTimeImmutable('2026-03-10'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Expense, '75.25', new DateTimeImmutable('2026-03-15'));

        // 300.50 - 75.25 = 225.25
        self::assertSame('225.25', $this->repository->netFlow($wallet));
    }

    public function testNetFlowAllTimeWhenBothBoundsNull(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        // Wildly spaced dates — both should be counted with null bounds
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '500.00', new DateTimeImmutable('2020-01-01'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '300.00', new DateTimeImmutable('2030-12-31'));

        self::assertSame('800.00', $this->repository->netFlow($wallet, null, null));
    }

    public function testNetFlowRespectsDateBounds(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '100.00', new DateTimeImmutable('2026-02-15'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '50.00', new DateTimeImmutable('2026-03-15'));
        $this->createTransaction($wallet, null, PersonalFinanceTransactionTypeEnum::Income, '25.00', new DateTimeImmutable('2026-04-15'));

        $from = new DateTimeImmutable('2026-03-01');
        $to = new DateTimeImmutable('2026-04-01');
        self::assertSame('50.00', $this->repository->netFlow($wallet, $from, $to));
    }

    public function testActualsByCategoryForMonthReturnsSumsKeyedByCategoryId(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat1 = $this->createCategory($wallet, 'Food');
        $cat2 = $this->createCategory($wallet, 'Travel');

        $this->createTransaction($wallet, $cat1, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-03-05'));
        $this->createTransaction($wallet, $cat1, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-03-10'));
        $this->createTransaction($wallet, $cat2, PersonalFinanceTransactionTypeEnum::Expense, '100.00', new DateTimeImmutable('2026-03-12'));
        // Outside the month — must be excluded
        $this->createTransaction($wallet, $cat1, PersonalFinanceTransactionTypeEnum::Expense, '999.99', new DateTimeImmutable('2026-02-28'));

        $from = new DateTimeImmutable('2026-03-01');
        $to = new DateTimeImmutable('2026-04-01');
        $actuals = $this->repository->actualsByCategoryForMonth($wallet, $from, $to);

        self::assertArrayHasKey($cat1->getId(), $actuals);
        self::assertArrayHasKey($cat2->getId(), $actuals);
        self::assertSame('40.00', $actuals[$cat1->getId()]);
        self::assertSame('100.00', $actuals[$cat2->getId()]);
    }

    public function testActualsByCategoryForMonthExcludesTransferLegs(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        // Regular expense
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-10'));
        // Transfer leg with same category — must be excluded
        $this->createTransaction(
            $wallet,
            $cat,
            PersonalFinanceTransactionTypeEnum::Expense,
            '500.00',
            new DateTimeImmutable('2026-03-15'),
            transferId: 'some-transfer-id',
        );

        $from = new DateTimeImmutable('2026-03-01');
        $to = new DateTimeImmutable('2026-04-01');
        $actuals = $this->repository->actualsByCategoryForMonth($wallet, $from, $to);

        self::assertSame('20.00', $actuals[$cat->getId()]);
    }

    public function testFindPaginatedByWalletFiltersByTag(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $taggedHoliday = $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '10.00', new DateTimeImmutable('2026-03-01'), 'restaurant nice');
        $taggedHoliday->setTags(['holiday', 'lunch']);
        $taggedDeductible = $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '20.00', new DateTimeImmutable('2026-03-02'), 'pro lunch');
        $taggedDeductible->setTags(['deductible']);
        $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '30.00', new DateTimeImmutable('2026-03-03'), 'no tag');
        $this->entityManager->flush();

        $byHoliday = $this->repository->findPaginatedByWallet($wallet, 1, 30, tag: 'holiday');
        self::assertSame(1, $byHoliday['total']);
        self::assertSame($taggedHoliday->getId(), $byHoliday['items'][0]->getId());

        $byDeductible = $this->repository->findPaginatedByWallet($wallet, 1, 30, tag: 'deductible');
        self::assertSame(1, $byDeductible['total']);
        self::assertSame($taggedDeductible->getId(), $byDeductible['items'][0]->getId());

        // Substring guard : searching "holid" must not match "holiday" since the
        // pattern wraps the tag in JSON quotes.
        $partial = $this->repository->findPaginatedByWallet($wallet, 1, 30, tag: 'holid');
        self::assertSame(0, $partial['total']);

        // Unknown tag → empty paginated payload (no exception). totalPages
        // follows the PaginationTrait convention (clamped to ≥ 1).
        $unknown = $this->repository->findPaginatedByWallet($wallet, 1, 30, tag: 'nonexistent');
        self::assertSame(0, $unknown['total']);
        self::assertSame([], $unknown['items']);
    }
}
