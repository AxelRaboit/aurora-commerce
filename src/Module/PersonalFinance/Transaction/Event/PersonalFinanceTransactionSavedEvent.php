<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Event;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a transaction is created or updated (and flushed).
 * `previousCategoryId` is set on update when the category changed —
 * subscribers that maintain per-category aggregates (Goal sync,
 * categorization learning, …) use it to know which category lost the
 * transaction.
 */
final class PersonalFinanceTransactionSavedEvent extends Event
{
    public function __construct(
        public readonly PersonalFinanceTransactionInterface $transaction,
        public readonly bool $isNew,
        public readonly ?int $previousCategoryId = null,
    ) {}
}
