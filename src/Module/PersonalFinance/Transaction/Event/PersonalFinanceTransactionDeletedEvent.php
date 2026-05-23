<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Event;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a transaction is removed (and flushed). The entity
 * has been detached from the EntityManager, so we capture the fields
 * that subscribers commonly need (user + category) up-front rather
 * than pass the dead entity.
 */
final class PersonalFinanceTransactionDeletedEvent extends Event
{
    public function __construct(
        public readonly CoreUserInterface $user,
        public readonly ?int $categoryId,
        public readonly int $walletId,
    ) {}
}
