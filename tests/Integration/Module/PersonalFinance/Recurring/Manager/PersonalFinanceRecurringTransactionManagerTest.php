<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceRecurringTransactionInput;
use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceRecurringTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceRecurringTransactionManagerTest extends PersonalFinanceTestCase
{
    private PersonalFinanceRecurringTransactionManagerInterface $manager;
    private PersonalFinanceRecurringTransactionRepository $recurringRepository;
    private PersonalFinanceTransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->getService(PersonalFinanceRecurringTransactionManagerInterface::class);
        $this->recurringRepository = $this->getService(PersonalFinanceRecurringTransactionRepository::class);
        $this->transactionRepository = $this->getService(PersonalFinanceTransactionRepository::class);
    }

    public function testCreateUpdateDeleteLifecycle(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '850.00',
            description: 'Monthly rent',
            dayOfMonth: 5,
            active: true,
        ));

        self::assertNotNull($rec->getId());
        self::assertSame('850.00', $rec->getAmount());
        self::assertSame(5, $rec->getDayOfMonth());

        $this->manager->update($rec, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '900.00',
            description: 'Updated rent',
            dayOfMonth: 10,
            active: true,
        ));

        self::assertSame('900.00', $rec->getAmount());
        self::assertSame(10, $rec->getDayOfMonth());
        self::assertSame('Updated rent', $rec->getDescription());

        $id = $rec->getId();
        $this->manager->delete($rec);

        $this->entityManager->clear();
        self::assertNull($this->recurringRepository->find($id));
    }

    public function testToggleFlipsActiveFlag(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            dayOfMonth: 5,
            active: true,
        ));

        $this->manager->toggle($rec);
        self::assertFalse($rec->isActive());

        $this->manager->toggle($rec);
        self::assertTrue($rec->isActive());
    }

    public function testGenerateIfDueCreatesTransactionWhenDayHasPassed(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '850.00',
            description: 'Rent',
            dayOfMonth: 5,
            active: true,
        ));

        // Simulate today = March 15, 2026 (day 15 ≥ 5)
        $today = new DateTimeImmutable('2026-03-15');

        $tx = $this->manager->generateIfDue($rec, $today);

        self::assertNotNull($tx);
        self::assertSame('850.00', $tx->getAmount());
        self::assertSame(PersonalFinanceTransactionTypeEnum::Expense, $tx->getType());
        self::assertSame('2026-03-05', $tx->getDate()->format('Y-m-d'));
        self::assertSame('2026-03', $rec->getLastGeneratedAt()?->format('Y-m'));
    }

    public function testGenerateIfDueDoesNothingBeforeDayOfMonth(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '850.00',
            dayOfMonth: 20,
            active: true,
        ));

        $today = new DateTimeImmutable('2026-03-05');
        $tx = $this->manager->generateIfDue($rec, $today);

        self::assertNull($tx);
        self::assertNull($rec->getLastGeneratedAt());
    }

    public function testGenerateIfDueDoesNothingWhenInactive(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '850.00',
            dayOfMonth: 5,
            active: false,
        ));

        $today = new DateTimeImmutable('2026-03-15');
        $tx = $this->manager->generateIfDue($rec, $today);

        self::assertNull($tx);
    }

    public function testGenerateIfDueIsIdempotentWithinSameMonth(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            dayOfMonth: 5,
            active: true,
        ));

        $today = new DateTimeImmutable('2026-03-15');
        $tx1 = $this->manager->generateIfDue($rec, $today);
        $tx2 = $this->manager->generateIfDue($rec, $today);

        self::assertNotNull($tx1);
        self::assertNull($tx2, 'Second call within same month must be a no-op');

        // Only one transaction was generated
        $txCount = count($this->transactionRepository->findByWallet($wallet));
        self::assertSame(1, $txCount);
    }

    public function testGenerateIfDueRunsAgainNextMonth(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Rent');

        $rec = $this->manager->create($user, new PersonalFinanceRecurringTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            dayOfMonth: 5,
            active: true,
        ));

        $marchTx = $this->manager->generateIfDue($rec, new DateTimeImmutable('2026-03-15'));
        $aprilTx = $this->manager->generateIfDue($rec, new DateTimeImmutable('2026-04-10'));

        self::assertNotNull($marchTx);
        self::assertNotNull($aprilTx);
        self::assertSame('2026-03-05', $marchTx->getDate()->format('Y-m-d'));
        self::assertSame('2026-04-05', $aprilTx->getDate()->format('Y-m-d'));
    }
}
