<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\EventSubscriber;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Goal\Manager\PersonalFinanceGoalManagerInterface;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionDeletedEvent;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionSavedEvent;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Keeps PersonalFinanceGoal::savedAmount in sync with the underlying
 * transactions every time a transaction in the goal's tracked category
 * is created, edited or removed.
 *
 * Mirrors Spendly's TransactionObserver. When a transaction's category
 * changes, both the new and the old category's goals are recomputed —
 * a category move shouldn't leave stale aggregates behind.
 */
final readonly class PersonalFinanceGoalSyncSubscriber
{
    public function __construct(
        private PersonalFinanceGoalRepository $goalRepository,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceGoalManagerInterface $goalManager,
    ) {}

    #[AsEventListener(event: PersonalFinanceTransactionSavedEvent::class)]
    public function onTransactionSaved(PersonalFinanceTransactionSavedEvent $event): void
    {
        $tx = $event->transaction;
        $this->syncForCategoryId($tx->getUser(), $tx->getCategory()?->getId());

        if (null !== $event->previousCategoryId && $event->previousCategoryId !== $tx->getCategory()?->getId()) {
            $this->syncForCategoryId($tx->getUser(), $event->previousCategoryId);
        }
    }

    #[AsEventListener(event: PersonalFinanceTransactionDeletedEvent::class)]
    public function onTransactionDeleted(PersonalFinanceTransactionDeletedEvent $event): void
    {
        $this->syncForCategoryId($event->user, $event->categoryId);
    }

    /**
     * Recomputes every goal sitting on the (user, category) pair. There
     * can be more than one row now that goals are wallet-scopable:
     * - the cross-wallet variant (`wallet = NULL`) which aggregates over
     *   every wallet of the user
     * - one row per specific wallet, scoping its sum to that wallet
     * All matching goals get a fresh `savedAmount` so a tx edit that
     * affects multiple goals (e.g. moving a wallet-A tx into the
     * category) keeps every aggregate honest.
     */
    private function syncForCategoryId(CoreUserInterface $user, ?int $categoryId): void
    {
        if (null === $categoryId) {
            return;
        }
        $category = $this->categoryRepository->find($categoryId);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return;
        }
        foreach ($this->goalRepository->findByCategoryForUser($user, $category) as $goal) {
            $this->goalManager->recomputeSavedAmount($goal);
        }
    }
}
