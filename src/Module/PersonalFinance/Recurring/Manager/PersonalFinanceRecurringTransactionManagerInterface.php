<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceRecurringTransactionInputInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceRecurringTransactionManagerInterface
{
    public function create(CoreUserInterface $user, PersonalFinanceRecurringTransactionInputInterface $input): PersonalFinanceRecurringTransactionInterface;

    public function update(PersonalFinanceRecurringTransactionInterface $rec, PersonalFinanceRecurringTransactionInputInterface $input): void;

    public function delete(PersonalFinanceRecurringTransactionInterface $rec): void;

    /**
     * Flip active flag. When activating a rule whose dayOfMonth has
     * already passed this month, immediately runs generateIfDue so the
     * user doesn't have to wait for the next month's cron pass.
     */
    public function toggle(PersonalFinanceRecurringTransactionInterface $rec): void;

    /**
     * Generates the matching PersonalFinanceTransaction if all gates
     * pass (active, dayOfMonth <= today, not already generated this
     * month). Returns null when the rule is skipped, or the freshly
     * created transaction otherwise.
     */
    public function generateIfDue(PersonalFinanceRecurringTransactionInterface $rec, ?DateTimeImmutable $today = null): ?PersonalFinanceTransactionInterface;
}
