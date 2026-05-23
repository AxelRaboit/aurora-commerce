<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Copies BudgetItem rows flagged `repeatNextMonth=true` from the
 * previous month's budget onto a freshly-created one. Triggered by
 * `BudgetManager::ensureForMonth` the first time the user opens a
 * new month — saves the manual re-entry of every recurring line
 * (rent, subscriptions, fixed bills).
 *
 * Non-`final` + `protected readonly` props so a client can subclass
 * the service to override `cloneItem()` (e.g. to also carry over the
 * previous month's diff into the new `carriedOver` column, which V1
 * deliberately keeps at zero to leave that decision to the user).
 */
#[AsAlias(PersonalFinanceBudgetRolloverServiceInterface::class)]
class PersonalFinanceBudgetRolloverService implements PersonalFinanceBudgetRolloverServiceInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PersonalFinanceBudgetRepository $budgetRepository,
        protected readonly PersonalFinanceBudgetItemRepository $itemRepository,
    ) {}

    /**
     * Clones the previous month's repeatable items onto $newBudget.
     * Does nothing if there's no previous-month budget (first month
     * the user uses the module on this wallet) or if the previous
     * month had zero items flagged repeatNextMonth.
     *
     * @return int number of items actually copied
     */
    public function rolloverFrom(PersonalFinanceBudgetInterface $newBudget): int
    {
        $previousMonth = $newBudget->getMonth()->modify('first day of previous month');
        $previousBudget = $this->budgetRepository->findByWalletAndMonth($newBudget->getWallet(), $previousMonth);
        if (!$previousBudget instanceof PersonalFinanceBudgetInterface) {
            return 0;
        }

        $sources = $this->itemRepository->findRepeatableByBudget($previousBudget);
        if ([] === $sources) {
            return 0;
        }

        foreach ($sources as $source) {
            $clone = $this->cloneItem($source, $newBudget);
            $this->entityManager->persist($clone);
        }

        $this->entityManager->flush();

        return count($sources);
    }

    /**
     * Hook: instantiate the concrete BudgetItem class. Mirrors
     * BudgetItemManager::createItem so client subclasses returning a
     * substituted entity are honoured here too.
     */
    protected function createItem(): PersonalFinanceBudgetItemInterface
    {
        return new PersonalFinanceBudgetItem();
    }

    /**
     * Hook: copy the source item's persistent fields onto a brand-new
     * BudgetItem attached to $newBudget. `carriedOver` deliberately
     * resets to `'0.00'` — V1 doesn't auto-roll the diff to keep the
     * carry semantics in the user's hands. Override to apply a
     * different policy (e.g. carry under-spend, cap at zero, etc.).
     */
    protected function cloneItem(PersonalFinanceBudgetItemInterface $source, PersonalFinanceBudgetInterface $newBudget): PersonalFinanceBudgetItemInterface
    {
        $clone = $this->createItem();
        $clone->setBudget($newBudget);
        $clone->setSection($source->getSection());
        $clone->setLabel($source->getLabel());
        $clone->setPlannedAmount($source->getPlannedAmount());
        $clone->setCarriedOver('0.00');
        $clone->setCategory($source->getCategory());
        $clone->setPosition($source->getPosition());
        $clone->setNotes($source->getNotes());
        $clone->setRepeatNextMonth(true);

        return $clone;
    }
}
