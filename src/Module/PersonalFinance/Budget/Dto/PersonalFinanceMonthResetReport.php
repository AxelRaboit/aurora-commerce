<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

/**
 * Outcome of `PersonalFinanceMonthResetService::reset`. Drives the
 * success toast: "{deletedTransactions} transactions supprimées,
 * budget vidé".
 */
class PersonalFinanceMonthResetReport
{
    public function __construct(
        public readonly int $deletedTransactions,
        public readonly bool $budgetCleared,
    ) {}
}
