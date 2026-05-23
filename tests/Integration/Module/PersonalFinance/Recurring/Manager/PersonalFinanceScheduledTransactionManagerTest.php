<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceScheduledTransactionInput;
use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceScheduledTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;
use DomainException;

final class PersonalFinanceScheduledTransactionManagerTest extends PersonalFinanceTestCase
{
    private PersonalFinanceScheduledTransactionManagerInterface $manager;
    private PersonalFinanceScheduledTransactionRepository $scheduledRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->getService(PersonalFinanceScheduledTransactionManagerInterface::class);
        $this->scheduledRepository = $this->getService(PersonalFinanceScheduledTransactionRepository::class);
    }

    public function testCreateUpdateDeleteLifecycle(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Tax');

        $sched = $this->manager->create($user, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '1200.00',
            description: 'Quarterly tax',
            scheduledDate: new DateTimeImmutable('2026-04-15'),
        ));

        self::assertNotNull($sched->getId());
        self::assertSame('1200.00', $sched->getAmount());
        self::assertFalse($sched->isGenerated());

        $this->manager->update($sched, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '1500.00',
            description: 'Tax adjusted',
            scheduledDate: new DateTimeImmutable('2026-04-20'),
        ));

        self::assertSame('1500.00', $sched->getAmount());
        self::assertSame('2026-04-20', $sched->getScheduledDate()->format('Y-m-d'));
        self::assertSame('Tax adjusted', $sched->getDescription());

        $id = $sched->getId();
        $this->manager->delete($sched);

        $this->entityManager->clear();
        self::assertNull($this->scheduledRepository->find($id));
    }

    public function testMaterializeCreatesUnderlyingTransactionAndMarksGenerated(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Tax');

        $sched = $this->manager->create($user, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '1200.00',
            description: 'Quarterly tax',
            scheduledDate: new DateTimeImmutable('2026-04-15'),
        ));

        $tx = $this->manager->materialize($sched);

        self::assertNotNull($tx->getId());
        self::assertSame((int) $wallet->getId(), $tx->getWallet()->getId());
        self::assertSame($cat->getId(), $tx->getCategory()?->getId());
        self::assertSame('1200.00', $tx->getAmount());
        self::assertSame(PersonalFinanceTransactionTypeEnum::Expense, $tx->getType());
        self::assertSame('2026-04-15', $tx->getDate()->format('Y-m-d'));
        self::assertTrue($sched->isGenerated());
    }

    public function testMaterializeTwiceThrows(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Tax');

        $sched = $this->manager->create($user, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            scheduledDate: new DateTimeImmutable('2026-04-15'),
        ));

        $this->manager->materialize($sched);

        $this->expectException(DomainException::class);
        $this->manager->materialize($sched);
    }

    public function testMaterializeBeforeScheduledDateIsAllowed(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Future');

        // Scheduled for a date 1 year in the future
        $sched = $this->manager->create($user, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            scheduledDate: (new DateTimeImmutable('today'))->modify('+1 year'),
        ));

        $tx = $this->manager->materialize($sched);

        self::assertNotNull($tx);
        self::assertTrue($sched->isGenerated());
    }

    public function testUpdateAfterMaterializeThrows(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Future');

        $sched = $this->manager->create($user, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '100.00',
            scheduledDate: new DateTimeImmutable('2026-04-15'),
        ));

        $this->manager->materialize($sched);

        $this->expectException(DomainException::class);
        $this->manager->update($sched, new PersonalFinanceScheduledTransactionInput(
            walletId: (int) $wallet->getId(),
            categoryId: $cat->getId(),
            type: PersonalFinanceTransactionTypeEnum::Expense,
            amount: '999.00',
            scheduledDate: new DateTimeImmutable('2026-04-15'),
        ));
    }
}
