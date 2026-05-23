<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceScheduledTransactionInputInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceScheduledTransactionManagerInterface
{
    public function create(CoreUserInterface $user, PersonalFinanceScheduledTransactionInputInterface $input): PersonalFinanceScheduledTransactionInterface;

    public function update(PersonalFinanceScheduledTransactionInterface $sched, PersonalFinanceScheduledTransactionInputInterface $input): void;

    public function delete(PersonalFinanceScheduledTransactionInterface $sched): void;

    /**
     * Spawns a real PersonalFinanceTransaction from the scheduled rule
     * and flags it generated. The scheduled row itself stays in DB as
     * history (Spendly-compatible) — refuses to re-materialize an
     * already-generated row.
     */
    public function materialize(PersonalFinanceScheduledTransactionInterface $sched): PersonalFinanceTransactionInterface;
}
