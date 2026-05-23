<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetItemInputInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetItemManagerInterface::class)]
class PersonalFinanceBudgetItemManager implements PersonalFinanceBudgetItemManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
    ) {}

    public function create(PersonalFinanceBudgetInterface $budget, PersonalFinanceBudgetItemInputInterface $input): PersonalFinanceBudgetItemInterface
    {
        $item = $this->createItem();
        $item->setBudget($budget);
        $this->applyInput($item, $input);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $this->auditCreated($item);

        return $item;
    }

    public function update(PersonalFinanceBudgetItemInterface $item, PersonalFinanceBudgetItemInputInterface $input): void
    {
        $this->applyInput($item, $input);
        $this->entityManager->flush();

        $this->auditUpdated($item);
    }

    public function delete(PersonalFinanceBudgetItemInterface $item): void
    {
        $this->auditDeleted($item);

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    protected function createItem(): PersonalFinanceBudgetItemInterface
    {
        return new PersonalFinanceBudgetItem();
    }

    protected function applyInput(PersonalFinanceBudgetItemInterface $item, PersonalFinanceBudgetItemInputInterface $input): void
    {
        $item->setSection($input->getSection());
        $item->setLabel($input->getLabel());
        $item->setPlannedAmount($input->getPlannedAmount());
        $item->setCarriedOver($input->getCarriedOver());
        $item->setPosition($input->getPosition());
        $item->setNotes($input->getNotes());
        $item->setRepeatNextMonth($input->repeatsNextMonth());
        $item->setCategory($this->resolveCategory($item->getBudget(), $input->getCategoryId()));
    }

    /**
     * Hook: only categories from the same wallet as the budget are
     * allowed — otherwise the actuals computed at read time would join
     * across wallets, polluting the budget view.
     */
    protected function resolveCategory(PersonalFinanceBudgetInterface $budget, ?int $categoryId): ?PersonalFinanceCategoryInterface
    {
        if (null === $categoryId) {
            return null;
        }

        $category = $this->categoryRepository->find($categoryId);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return null;
        }

        if ($category->getWallet()->getId() !== $budget->getWallet()->getId()) {
            return null;
        }

        return $category;
    }

    protected function auditCreated(PersonalFinanceBudgetItemInterface $item): void
    {
        $this->auditLogger->log('personal_finance', 'budget_item.created', 'PersonalFinanceBudgetItem', $item->getId(), $this->auditPayload($item));
    }

    protected function auditUpdated(PersonalFinanceBudgetItemInterface $item): void
    {
        $this->auditLogger->log('personal_finance', 'budget_item.updated', 'PersonalFinanceBudgetItem', $item->getId(), $this->auditPayload($item));
    }

    protected function auditDeleted(PersonalFinanceBudgetItemInterface $item): void
    {
        $this->auditLogger->log('personal_finance', 'budget_item.deleted', 'PersonalFinanceBudgetItem', $item->getId(), $this->auditPayload($item));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceBudgetItemInterface $item): array
    {
        return [
            'budgetId' => $item->getBudget()->getId(),
            'walletId' => $item->getBudget()->getWallet()->getId(),
            'section' => $item->getSection()->value,
            'label' => $item->getLabel(),
            'plannedAmount' => $item->getPlannedAmount(),
            'carriedOver' => $item->getCarriedOver(),
            'categoryId' => $item->getCategory()?->getId(),
        ];
    }
}
