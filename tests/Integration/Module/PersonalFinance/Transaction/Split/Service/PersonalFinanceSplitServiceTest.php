<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Transaction\Split\Service;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionDeletedEvent;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionSavedEvent;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitInput;
use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitPart;
use Aurora\Module\PersonalFinance\Transaction\Split\Service\PersonalFinanceSplitServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;
use DomainException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PersonalFinanceSplitServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceSplitServiceInterface $service;
    private PersonalFinanceTransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceSplitServiceInterface::class);
        $this->transactionRepository = $this->getService(PersonalFinanceTransactionRepository::class);
    }

    public function testCreateSplitPersistsAllPartsSharingTheSameSplitId(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');
        $cat1 = $this->createCategory($wallet, 'Cat1');
        $cat2 = $this->createCategory($wallet, 'Cat2');

        $splitId = $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
            type: PersonalFinanceTransactionTypeEnum::Expense,
            date: new DateTimeImmutable('2026-03-15'),
            description: 'Grocery split',
            parts: [
                new PersonalFinanceSplitPart(categoryId: $cat1->getId(), amount: '30.00'),
                new PersonalFinanceSplitPart(categoryId: $cat2->getId(), amount: '15.50'),
            ],
        ));

        $legs = $this->transactionRepository->findBySplitId($splitId);
        self::assertCount(2, $legs);

        foreach ($legs as $leg) {
            self::assertSame($splitId, $leg->getSplitId());
            self::assertSame(PersonalFinanceTransactionTypeEnum::Expense, $leg->getType());
            self::assertSame('2026-03-15', $leg->getDate()->format('Y-m-d'));
        }

        $amounts = array_map(static fn ($l) => $l->getAmount(), $legs);
        sort($amounts);
        self::assertSame(['15.50', '30.00'], $amounts);

        $categoryIds = array_map(static fn ($l): ?int => $l->getCategory()?->getId(), $legs);
        self::assertContains($cat1->getId(), $categoryIds);
        self::assertContains($cat2->getId(), $categoryIds);
    }

    public function testCreateSplitDispatchesSavedEventPerLeg(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');
        $cat1 = $this->createCategory($wallet, 'Cat1');
        $cat2 = $this->createCategory($wallet, 'Cat2');

        $dispatcher = $this->getService(EventDispatcherInterface::class);
        $captured = [];
        $listener = static function (PersonalFinanceTransactionSavedEvent $event) use (&$captured): void {
            $captured[] = $event;
        };
        $dispatcher->addListener(PersonalFinanceTransactionSavedEvent::class, $listener);

        try {
            $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
                type: PersonalFinanceTransactionTypeEnum::Expense,
                date: new DateTimeImmutable('2026-03-15'),
                description: 'Split',
                parts: [
                    new PersonalFinanceSplitPart(categoryId: $cat1->getId(), amount: '10.00'),
                    new PersonalFinanceSplitPart(categoryId: $cat2->getId(), amount: '20.00'),
                ],
            ));
        } finally {
            $dispatcher->removeListener(PersonalFinanceTransactionSavedEvent::class, $listener);
        }

        self::assertCount(2, $captured);
        foreach ($captured as $event) {
            self::assertTrue($event->isNew);
            self::assertNotNull($event->transaction->getId(), 'event must fire AFTER commit (tx has an id)');
        }
    }

    public function testCreateSplitRejectsLessThanTwoParts(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');
        $cat = $this->createCategory($wallet, 'Cat');

        $this->expectException(DomainException::class);
        $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
            type: PersonalFinanceTransactionTypeEnum::Expense,
            date: new DateTimeImmutable('2026-03-15'),
            parts: [
                new PersonalFinanceSplitPart(categoryId: $cat->getId(), amount: '10.00'),
            ],
        ));
    }

    public function testCreateSplitRejectsCategoryFromAnotherWallet(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W1', '500.00');
        $otherWallet = $this->createWallet($user, 'W2', '0.00');
        $cat1 = $this->createCategory($wallet, 'Cat1');
        $foreignCat = $this->createCategory($otherWallet, 'Foreign');

        $this->expectException(DomainException::class);
        $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
            type: PersonalFinanceTransactionTypeEnum::Expense,
            date: new DateTimeImmutable('2026-03-15'),
            parts: [
                new PersonalFinanceSplitPart(categoryId: $cat1->getId(), amount: '10.00'),
                new PersonalFinanceSplitPart(categoryId: $foreignCat->getId(), amount: '20.00'),
            ],
        ));
    }

    public function testDeleteSplitRemovesAllLegsAtomically(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');
        $cat1 = $this->createCategory($wallet, 'Cat1');
        $cat2 = $this->createCategory($wallet, 'Cat2');

        $splitId = $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
            type: PersonalFinanceTransactionTypeEnum::Expense,
            date: new DateTimeImmutable('2026-03-15'),
            parts: [
                new PersonalFinanceSplitPart(categoryId: $cat1->getId(), amount: '10.00'),
                new PersonalFinanceSplitPart(categoryId: $cat2->getId(), amount: '20.00'),
            ],
        ));

        $this->service->delete($splitId);

        $legs = $this->transactionRepository->findBySplitId($splitId);
        self::assertCount(0, $legs);
    }

    public function testDeleteSplitDispatchesDeletedEventPerLegWithSnapshotData(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');
        $cat1 = $this->createCategory($wallet, 'Cat1');
        $cat2 = $this->createCategory($wallet, 'Cat2');

        $splitId = $this->service->create($user, $wallet, new PersonalFinanceSplitInput(
            type: PersonalFinanceTransactionTypeEnum::Expense,
            date: new DateTimeImmutable('2026-03-15'),
            parts: [
                new PersonalFinanceSplitPart(categoryId: $cat1->getId(), amount: '10.00'),
                new PersonalFinanceSplitPart(categoryId: $cat2->getId(), amount: '20.00'),
            ],
        ));

        $dispatcher = $this->getService(EventDispatcherInterface::class);
        $captured = [];
        $listener = static function (PersonalFinanceTransactionDeletedEvent $event) use (&$captured): void {
            $captured[] = $event;
        };
        $dispatcher->addListener(PersonalFinanceTransactionDeletedEvent::class, $listener);

        try {
            $this->service->delete($splitId);
        } finally {
            $dispatcher->removeListener(PersonalFinanceTransactionDeletedEvent::class, $listener);
        }

        self::assertCount(2, $captured);

        $categoryIds = [];
        foreach ($captured as $event) {
            self::assertSame($user->getId(), $event->user->getId(), 'event must carry the pre-delete user, not null');
            self::assertSame((int) $wallet->getId(), $event->walletId);
            $categoryIds[] = $event->categoryId;
        }

        self::assertContains($cat1->getId(), $categoryIds);
        self::assertContains($cat2->getId(), $categoryIds);
    }

    public function testDeleteRejectsUnknownSplitId(): void
    {
        $this->expectException(DomainException::class);
        $this->service->delete('00000000-0000-0000-0000-000000000000');
    }
}
