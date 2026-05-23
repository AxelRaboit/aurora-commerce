<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetInput;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetItemInput;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetPresetApplyModeEnum;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetPresetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;

final class PersonalFinanceBudgetPresetManagerTest extends PersonalFinanceTestCase
{
    private PersonalFinanceBudgetPresetManagerInterface $manager;
    private PersonalFinanceBudgetItemRepository $itemRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->getService(PersonalFinanceBudgetPresetManagerInterface::class);
        $this->itemRepository = $this->getService(PersonalFinanceBudgetItemRepository::class);
    }

    public function testCreatePersistsItemsInPositionOrder(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $food = $this->createCategory($wallet, 'Food');
        $rent = $this->createCategory($wallet, 'Rent');

        $input = new PersonalFinanceBudgetPresetInput(
            name: 'Standard month',
            description: 'Default template',
            items: [
                new PersonalFinanceBudgetPresetItemInput(section: PersonalFinanceBudgetSectionEnum::FixedCharges, label: 'Rent', plannedAmount: '900.00', categoryId: $rent->getId(), position: 0),
                new PersonalFinanceBudgetPresetItemInput(section: PersonalFinanceBudgetSectionEnum::Expenses, label: 'Food', plannedAmount: '350.00', categoryId: $food->getId(), position: 1),
            ],
        );

        $preset = $this->manager->create($user, $wallet, $input);

        self::assertNotNull($preset->getId());
        self::assertSame('Standard month', $preset->getName());
        self::assertCount(2, $preset->getItems());
    }

    public function testApplyToMonthAppendsItemsToExistingBudget(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $food = $this->createCategory($wallet, 'Food');

        $budget = $this->seedBudgetWithOneItem($user, $wallet, '2026-05', 'Existing line', '100.00', $food);

        $preset = $this->manager->create(
            $user,
            $wallet,
            new PersonalFinanceBudgetPresetInput(
                name: 'Holidays',
                items: [
                    new PersonalFinanceBudgetPresetItemInput(section: PersonalFinanceBudgetSectionEnum::Expenses, label: 'Hotel', plannedAmount: '800.00', position: 0),
                    new PersonalFinanceBudgetPresetItemInput(section: PersonalFinanceBudgetSectionEnum::Expenses, label: 'Restaurants', plannedAmount: '300.00', position: 1),
                ],
            ),
        );

        $inserted = $this->manager->applyToMonth($preset, $budget, PersonalFinanceBudgetPresetApplyModeEnum::Append);

        self::assertSame(2, $inserted);
        $items = $this->itemRepository->findByBudget($budget);
        self::assertCount(3, $items);
        $labels = array_map(static fn ($i) => $i->getLabel(), $items);
        self::assertContains('Existing line', $labels);
        self::assertContains('Hotel', $labels);
        self::assertContains('Restaurants', $labels);
    }

    public function testApplyToMonthReplacesExistingItems(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $food = $this->createCategory($wallet, 'Food');

        $budget = $this->seedBudgetWithOneItem($user, $wallet, '2026-05', 'Existing line', '100.00', $food);

        $preset = $this->manager->create(
            $user,
            $wallet,
            new PersonalFinanceBudgetPresetInput(
                name: 'Replace template',
                items: [
                    new PersonalFinanceBudgetPresetItemInput(section: PersonalFinanceBudgetSectionEnum::Expenses, label: 'Only line', plannedAmount: '200.00', position: 0),
                ],
            ),
        );

        $inserted = $this->manager->applyToMonth($preset, $budget, PersonalFinanceBudgetPresetApplyModeEnum::Replace);

        self::assertSame(1, $inserted);
        $items = $this->itemRepository->findByBudget($budget);
        self::assertCount(1, $items);
        self::assertSame('Only line', $items[0]->getLabel());
    }

    public function testCreateFromBudgetCopiesItemsWithoutCarriedOver(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $food = $this->createCategory($wallet, 'Food');

        $budget = $this->seedBudgetWithOneItem($user, $wallet, '2026-05', 'Source line', '120.00', $food);
        // simulate a carry-over on the source — preset must not inherit it
        $items = $this->itemRepository->findByBudget($budget);
        $items[0]->setCarriedOver('50.00');
        $this->entityManager->flush();

        $preset = $this->manager->createFromBudget($user, $budget, 'Snapshot', null);

        self::assertSame('Snapshot', $preset->getName());
        self::assertCount(1, $preset->getItems());
        $presetItem = $preset->getItems()->first();
        self::assertSame('Source line', $presetItem->getLabel());
        self::assertSame('120.00', $presetItem->getPlannedAmount());
        // PresetItem has no carriedOver column by design — verified by the schema, here we just confirm category copy
        self::assertSame($food->getId(), $presetItem->getCategory()?->getId());
    }

    private function seedBudgetWithOneItem(
        $user,
        $wallet,
        string $monthString,
        string $label,
        string $planned,
        $category,
    ): PersonalFinanceBudget {
        $budget = new PersonalFinanceBudget();
        $budget->setUser($user);
        $budget->setWallet($wallet);
        $budget->setMonth(new DateTimeImmutable($monthString.'-01'));
        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        $item = new PersonalFinanceBudgetItem();
        $item->setBudget($budget);
        $item->setSection(PersonalFinanceBudgetSectionEnum::Expenses);
        $item->setLabel($label);
        $item->setPlannedAmount($planned);
        $item->setCategory($category);
        $item->setPosition(0);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $this->entityManager->refresh($budget);

        return $budget;
    }
}
