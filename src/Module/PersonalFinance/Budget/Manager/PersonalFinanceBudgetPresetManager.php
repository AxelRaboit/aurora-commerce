<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetInputInterface;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetItemInputInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPreset;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetPresetApplyModeEnum;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetPresetManagerInterface::class)]
class PersonalFinanceBudgetPresetManager implements PersonalFinanceBudgetPresetManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
    ) {}

    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceBudgetPresetInputInterface $input,
    ): PersonalFinanceBudgetPresetInterface {
        $preset = $this->createPreset();
        $preset->setUser($user);
        $preset->setWallet($wallet);
        $this->applyInput($preset, $input);

        $this->entityManager->persist($preset);
        $this->entityManager->flush();

        $this->auditCreated($preset);

        return $preset;
    }

    public function update(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetPresetInputInterface $input,
    ): void {
        // Clear and rebuild items — simpler than reconciling per-row diffs,
        // and the preset surface is tiny (no FK pointers from outside).
        foreach ($preset->getItems()->toArray() as $existing) {
            $this->entityManager->remove($existing);
        }
        $this->applyInput($preset, $input);

        $this->entityManager->flush();
        $this->auditUpdated($preset);
    }

    public function delete(PersonalFinanceBudgetPresetInterface $preset): void
    {
        $this->auditDeleted($preset);

        $this->entityManager->remove($preset);
        $this->entityManager->flush();
    }

    public function createFromBudget(
        CoreUserInterface $user,
        PersonalFinanceBudgetInterface $budget,
        string $name,
        ?string $description = null,
    ): PersonalFinanceBudgetPresetInterface {
        $preset = $this->createPreset();
        $preset->setUser($user);
        $preset->setWallet($budget->getWallet());
        $preset->setName($name);
        $preset->setDescription($description);

        $position = 0;
        foreach ($budget->getItems() as $sourceItem) {
            $presetItem = $this->createPresetItem();
            $presetItem->setPreset($preset);
            $presetItem->setSection($sourceItem->getSection());
            $presetItem->setLabel($sourceItem->getLabel());
            $presetItem->setPlannedAmount($sourceItem->getPlannedAmount());
            $presetItem->setCategory($sourceItem->getCategory());
            $presetItem->setNotes($sourceItem->getNotes());
            $presetItem->setPosition($position++);
            $preset->getItems()->add($presetItem);

            $this->entityManager->persist($presetItem);
        }

        $this->entityManager->persist($preset);
        $this->entityManager->flush();

        $this->auditCreated($preset);

        return $preset;
    }

    public function applyToMonth(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetInterface $budget,
        PersonalFinanceBudgetPresetApplyModeEnum $mode,
    ): int {
        if (PersonalFinanceBudgetPresetApplyModeEnum::Replace === $mode) {
            foreach ($budget->getItems()->toArray() as $existing) {
                $this->entityManager->remove($existing);
            }
            $this->entityManager->flush();
        }

        $insertedCount = 0;
        $basePosition = $this->nextPosition($budget);

        foreach ($preset->getItems() as $presetItem) {
            $budgetItem = $this->createBudgetItem();
            $budgetItem->setBudget($budget);
            $budgetItem->setSection($presetItem->getSection());
            $budgetItem->setLabel($presetItem->getLabel());
            $budgetItem->setPlannedAmount($presetItem->getPlannedAmount());
            $budgetItem->setCarriedOver('0.00');
            $budgetItem->setCategory($presetItem->getCategory());
            $budgetItem->setPosition($basePosition + $presetItem->getPosition());
            $budgetItem->setNotes($presetItem->getNotes());
            $budgetItem->setRepeatNextMonth(false);
            $budget->getItems()->add($budgetItem);

            $this->entityManager->persist($budgetItem);
            ++$insertedCount;
        }

        $this->entityManager->flush();

        $this->auditApplied($preset, $budget, $mode, $insertedCount);

        return $insertedCount;
    }

    protected function applyInput(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetPresetInputInterface $input,
    ): void {
        $preset->setName($input->getName());
        $preset->setDescription($input->getDescription());

        foreach ($input->getItems() as $itemInput) {
            $item = $this->createPresetItem();
            $item->setPreset($preset);
            $this->applyItemInput($item, $itemInput);
            $preset->getItems()->add($item);

            $this->entityManager->persist($item);
        }
    }

    protected function applyItemInput(
        PersonalFinanceBudgetPresetItemInterface $item,
        PersonalFinanceBudgetPresetItemInputInterface $input,
    ): void {
        $item->setSection($input->getSection());
        $item->setLabel($input->getLabel());
        $item->setPlannedAmount($input->getPlannedAmount());
        $item->setPosition($input->getPosition());
        $item->setNotes($input->getNotes());

        $categoryId = $input->getCategoryId();
        $item->setCategory(null === $categoryId ? null : $this->categoryRepository->find($categoryId));
    }

    /**
     * Highest existing position in the budget, +1. Lets `applyToMonth`
     * in Append mode append the preset items below user-curated ones
     * without shuffling existing positions.
     */
    protected function nextPosition(PersonalFinanceBudgetInterface $budget): int
    {
        $max = -1;
        foreach ($budget->getItems() as $item) {
            $max = max($max, $item->getPosition());
        }

        return $max + 1;
    }

    protected function createPreset(): PersonalFinanceBudgetPresetInterface
    {
        return new PersonalFinanceBudgetPreset();
    }

    protected function createPresetItem(): PersonalFinanceBudgetPresetItemInterface
    {
        return new PersonalFinanceBudgetPresetItem();
    }

    protected function createBudgetItem(): PersonalFinanceBudgetItemInterface
    {
        return new PersonalFinanceBudgetItem();
    }

    protected function auditCreated(PersonalFinanceBudgetPresetInterface $preset): void
    {
        $this->auditLogger->log('personal_finance', 'budget_preset.created', 'PersonalFinanceBudgetPreset', $preset->getId(), $this->auditPayload($preset));
    }

    protected function auditUpdated(PersonalFinanceBudgetPresetInterface $preset): void
    {
        $this->auditLogger->log('personal_finance', 'budget_preset.updated', 'PersonalFinanceBudgetPreset', $preset->getId(), $this->auditPayload($preset));
    }

    protected function auditDeleted(PersonalFinanceBudgetPresetInterface $preset): void
    {
        $this->auditLogger->log('personal_finance', 'budget_preset.deleted', 'PersonalFinanceBudgetPreset', $preset->getId(), $this->auditPayload($preset));
    }

    protected function auditApplied(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetInterface $budget,
        PersonalFinanceBudgetPresetApplyModeEnum $mode,
        int $insertedCount,
    ): void {
        $this->auditLogger->log(
            'personal_finance',
            'budget_preset.applied',
            'PersonalFinanceBudgetPreset',
            $preset->getId(),
            [
                'budgetId' => $budget->getId(),
                'month' => $budget->getMonth()->format('Y-m'),
                'mode' => $mode->value,
                'insertedCount' => $insertedCount,
            ] + $this->auditPayload($preset),
        );
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceBudgetPresetInterface $preset): array
    {
        return [
            'walletId' => $preset->getWallet()->getId(),
            'name' => $preset->getName(),
        ];
    }
}
