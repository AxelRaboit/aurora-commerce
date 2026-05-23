<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\EventSubscriber;

use Aurora\Module\PersonalFinance\Categorization\Service\PersonalFinanceCategorizationLearnServiceInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionSavedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Whenever a transaction is created or updated with both a description
 * and a (non-system) category, feeds the (description → category)
 * signal into the categorization rules so future transactions with the
 * same description get the category suggested automatically.
 *
 * Listens to PersonalFinanceTransactionSavedEvent — the same event the
 * Goal subscriber uses — so dispatch is already wired up from the
 * TransactionManager + SplitService.
 */
final readonly class PersonalFinanceCategorizationLearnSubscriber
{
    public function __construct(
        private PersonalFinanceCategorizationLearnServiceInterface $learnService,
    ) {}

    #[AsEventListener(event: PersonalFinanceTransactionSavedEvent::class)]
    public function onTransactionSaved(PersonalFinanceTransactionSavedEvent $event): void
    {
        $tx = $event->transaction;
        $category = $tx->getCategory();
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return;
        }

        $this->learnService->learn($tx->getUser(), $tx->getDescription(), $category);
    }
}
