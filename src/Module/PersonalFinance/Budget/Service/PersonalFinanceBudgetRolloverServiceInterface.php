<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;

interface PersonalFinanceBudgetRolloverServiceInterface
{
    /**
     * Clone the previous month's `repeatNextMonth=true` items onto
     * the freshly-created budget. Returns the number of items copied.
     */
    public function rolloverFrom(PersonalFinanceBudgetInterface $newBudget): int;
}
