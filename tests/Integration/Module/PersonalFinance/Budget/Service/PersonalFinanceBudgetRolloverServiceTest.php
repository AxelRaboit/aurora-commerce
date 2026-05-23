<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Service\PersonalFinanceBudgetRolloverServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

/**
 * Auto-rollover : when a brand-new monthly budget is created via
 * ensureForMonth, items flagged repeatNextMonth=true on the previous
 * month must be cloned onto the new month so the user doesn't
 * re-enter their recurring lines every month.
 */
final class PersonalFinanceBudgetRolloverServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceBudgetRolloverServiceInterface $rollover;
    private PersonalFinanceBudgetManagerInterface $budgetManager;
    private PersonalFinanceBudgetItemRepository $itemRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rollover = $this->getService(PersonalFinanceBudgetRolloverServiceInterface::class);
        $this->budgetManager = $this->getService(PersonalFinanceBudgetManagerInterface::class);
        $this->itemRepository = $this->getService(PersonalFinanceBudgetItemRepository::class);
    }

    public function testRolloverClonesRepeatableItemsToNewBudget(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $cat = $this->createCategory($wallet, 'Rent');

        $marchBudget = $this->createBudget($user, $wallet, '2026-03');
        $this->createItem($marchBudget, 'Rent', '850.00', $cat, repeatNextMonth: true);
        $this->createItem($marchBudget, 'Internet', '40.00', null, repeatNextMonth: true);
        $this->createItem($marchBudget, 'One-shot vacation prep', '120.00', null, repeatNextMonth: false);

        $aprilBudget = $this->createBudget($user, $wallet, '2026-04');
        $count = $this->rollover->rolloverFrom($aprilBudget);

        self::assertSame(2, $count);
        $aprilItems = $this->itemRepository->findByBudget($aprilBudget);
        self::assertCount(2, $aprilItems);
        $labels = array_map(static fn ($item) => $item->getLabel(), $aprilItems);
        self::assertContains('Rent', $labels);
        self::assertContains('Internet', $labels);
        self::assertNotContains('One-shot vacation prep', $labels);
    }

    public function testRolloverResetsCarriedOverToZero(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $marchBudget = $this->createBudget($user, $wallet, '2026-03');

        $item = $this->createItem($marchBudget, 'Rent', '850.00', null, repeatNextMonth: true);
        $item->setCarriedOver('150.00');
        $this->entityManager->flush();

        $aprilBudget = $this->createBudget($user, $wallet, '2026-04');
        $this->rollover->rolloverFrom($aprilBudget);

        $cloned = $this->itemRepository->findByBudget($aprilBudget)[0];
        self::assertSame('0.00', $cloned->getCarriedOver());
        // Source planned amount stays
        self::assertSame('850.00', $cloned->getPlannedAmount());
    }

    public function testRolloverIsNoOpWithoutPreviousBudget(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $marchBudget = $this->createBudget($user, $wallet, '2026-03');

        $count = $this->rollover->rolloverFrom($marchBudget);

        self::assertSame(0, $count);
        self::assertCount(0, $this->itemRepository->findByBudget($marchBudget));
    }

    public function testRolloverIsNoOpWhenPreviousBudgetHasNoRepeatItems(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $marchBudget = $this->createBudget($user, $wallet, '2026-03');
        $this->createItem($marchBudget, 'One-shot', '50.00', null, repeatNextMonth: false);

        $aprilBudget = $this->createBudget($user, $wallet, '2026-04');
        $count = $this->rollover->rolloverFrom($aprilBudget);

        self::assertSame(0, $count);
    }

    public function testEnsureForMonthTriggersAutoRolloverOnNewBudget(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $marchBudget = $this->createBudget($user, $wallet, '2026-03');
        $this->createItem($marchBudget, 'Rent', '850.00', null, repeatNextMonth: true);

        // First call → creates April + triggers rollover
        $aprilBudget = $this->budgetManager->ensureForMonth($user, $wallet, new DateTimeImmutable('2026-04-01'));

        self::assertSame(1, $this->budgetManager->lastRolloverCount());
        self::assertCount(1, $this->itemRepository->findByBudget($aprilBudget));

        // Second call on the same month → no rollover (budget already existed)
        $this->budgetManager->ensureForMonth($user, $wallet, new DateTimeImmutable('2026-04-01'));
        self::assertSame(0, $this->budgetManager->lastRolloverCount());
        self::assertCount(1, $this->itemRepository->findByBudget($aprilBudget));
    }

    public function testRepeatFlagPropagatesSoChainContinues(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user);
        $marchBudget = $this->createBudget($user, $wallet, '2026-03');
        $this->createItem($marchBudget, 'Rent', '850.00', null, repeatNextMonth: true);

        $aprilBudget = $this->budgetManager->ensureForMonth($user, $wallet, new DateTimeImmutable('2026-04-01'));
        $aprilItem = $this->itemRepository->findByBudget($aprilBudget)[0];

        // The cloned item itself keeps repeatNextMonth=true so it
        // chains forward into May, June, … unless the user toggles it.
        self::assertTrue($aprilItem->repeatsNextMonth());
    }

    /**
     * @param 'YYYY-MM'|string $monthKey
     */
    private function createBudget($user, $wallet, string $monthKey): PersonalFinanceBudget
    {
        $budget = new PersonalFinanceBudget();
        $budget->setUser($user);
        $budget->setWallet($wallet);
        $budget->setMonth(new DateTimeImmutable($monthKey.'-01'));
        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $budget;
    }

    private function createItem($budget, string $label, string $planned, $category, bool $repeatNextMonth): PersonalFinanceBudgetItem
    {
        $item = new PersonalFinanceBudgetItem();
        $item->setBudget($budget);
        $item->setSection(PersonalFinanceBudgetSectionEnum::FixedCharges);
        $item->setLabel($label);
        $item->setPlannedAmount($planned);
        $item->setCategory($category);
        $item->setRepeatNextMonth($repeatNextMonth);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
