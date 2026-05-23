<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Goal\EventSubscriber;

use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalInput;
use Aurora\Module\PersonalFinance\Goal\Enum\PersonalFinanceGoalTrackingModeEnum;
use Aurora\Module\PersonalFinance\Goal\Manager\PersonalFinanceGoalManagerInterface;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInput;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceGoalSyncSubscriberTest extends PersonalFinanceTestCase
{
    private PersonalFinanceTransactionManagerInterface $transactionManager;
    private PersonalFinanceGoalManagerInterface $goalManager;
    private PersonalFinanceGoalRepository $goalRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionManager = $this->getService(PersonalFinanceTransactionManagerInterface::class);
        $this->goalManager = $this->getService(PersonalFinanceGoalManagerInterface::class);
        $this->goalRepository = $this->getService(PersonalFinanceGoalRepository::class);
    }

    public function testSavingTransactionInLinkedCategoryBumpsGoalSavedAmount(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Savings');

        $goal = $this->goalManager->create($user, new PersonalFinanceGoalInput(
            name: 'Holiday',
            targetAmount: '1000.00',
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            trackingMode: PersonalFinanceGoalTrackingModeEnum::IncomeOnly,
        ));

        self::assertSame('0.00', $goal->getSavedAmount());

        $this->transactionManager->create($user, $wallet, new PersonalFinanceTransactionInput(
            type: PersonalFinanceTransactionTypeEnum::Income,
            amount: '150.00',
            date: new DateTimeImmutable('2026-03-15'),
            categoryId: $cat->getId(),
        ));

        $this->entityManager->clear();
        $reloaded = $this->goalRepository->find($goal->getId());
        self::assertNotNull($reloaded);
        self::assertSame('150.00', $reloaded->getSavedAmount());
    }

    public function testCategoryMoveRecomputesBothOldAndNewGoals(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $catA = $this->createCategory($wallet, 'CatA');
        $catB = $this->createCategory($wallet, 'CatB');

        $goalA = $this->goalManager->create($user, new PersonalFinanceGoalInput(
            name: 'A',
            targetAmount: '500.00',
            walletId: (int) $wallet->getId(),
            categoryId: $catA->getId(),
            trackingMode: PersonalFinanceGoalTrackingModeEnum::IncomeOnly,
        ));
        $goalB = $this->goalManager->create($user, new PersonalFinanceGoalInput(
            name: 'B',
            targetAmount: '500.00',
            walletId: (int) $wallet->getId(),
            categoryId: $catB->getId(),
            trackingMode: PersonalFinanceGoalTrackingModeEnum::IncomeOnly,
        ));

        $tx = $this->transactionManager->create($user, $wallet, new PersonalFinanceTransactionInput(
            type: PersonalFinanceTransactionTypeEnum::Income,
            amount: '100.00',
            date: new DateTimeImmutable('2026-03-15'),
            categoryId: $catA->getId(),
        ));

        $this->entityManager->clear();
        $aBefore = $this->goalRepository->find($goalA->getId());
        $bBefore = $this->goalRepository->find($goalB->getId());
        self::assertSame('100.00', $aBefore->getSavedAmount());
        self::assertSame('0.00', $bBefore->getSavedAmount());

        // Move tx from catA to catB
        $tx = $this->entityManager->find($tx::class, $tx->getId());
        $this->transactionManager->update($tx, new PersonalFinanceTransactionInput(
            type: PersonalFinanceTransactionTypeEnum::Income,
            amount: '100.00',
            date: new DateTimeImmutable('2026-03-15'),
            categoryId: $catB->getId(),
        ));

        $this->entityManager->clear();
        $aAfter = $this->goalRepository->find($goalA->getId());
        $bAfter = $this->goalRepository->find($goalB->getId());
        self::assertSame('0.00', $aAfter->getSavedAmount(), 'GoalA should be recomputed and drop to 0');
        self::assertSame('100.00', $bAfter->getSavedAmount(), 'GoalB should be recomputed and pick up the tx');
    }

    public function testDeletingTransactionInLinkedCategoryRecomputesGoal(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Savings');

        $goal = $this->goalManager->create($user, new PersonalFinanceGoalInput(
            name: 'Holiday',
            targetAmount: '1000.00',
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            trackingMode: PersonalFinanceGoalTrackingModeEnum::IncomeOnly,
        ));

        $tx = $this->transactionManager->create($user, $wallet, new PersonalFinanceTransactionInput(
            type: PersonalFinanceTransactionTypeEnum::Income,
            amount: '200.00',
            date: new DateTimeImmutable('2026-03-15'),
            categoryId: $cat->getId(),
        ));

        $this->entityManager->clear();
        self::assertSame('200.00', $this->goalRepository->find($goal->getId())->getSavedAmount());

        $tx = $this->entityManager->find($tx::class, $tx->getId());
        $this->transactionManager->delete($tx);

        $this->entityManager->clear();
        $reloaded = $this->goalRepository->find($goal->getId());
        self::assertSame('0.00', $reloaded->getSavedAmount());
    }

    public function testGoalNotLinkedToAnyCategoryStaysUntouchedByTransactionEvents(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Random');

        $manualGoal = $this->goalManager->create($user, new PersonalFinanceGoalInput(
            name: 'Manual',
            targetAmount: '500.00',
            walletId: (int) $wallet->getId(),
            categoryId: null,
        ));

        $this->transactionManager->create($user, $wallet, new PersonalFinanceTransactionInput(
            type: PersonalFinanceTransactionTypeEnum::Income,
            amount: '999.00',
            date: new DateTimeImmutable('2026-03-15'),
            categoryId: $cat->getId(),
        ));

        $this->entityManager->clear();
        $reloaded = $this->goalRepository->find($manualGoal->getId());
        self::assertSame('0.00', $reloaded->getSavedAmount(), 'Goal without category must stay untouched');
    }
}
