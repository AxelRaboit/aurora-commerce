<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetPresetInputInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetPresetApplyModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceBudgetPresetManagerInterface
{
    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceBudgetPresetInputInterface $input,
    ): PersonalFinanceBudgetPresetInterface;

    public function update(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetPresetInputInterface $input,
    ): void;

    public function delete(PersonalFinanceBudgetPresetInterface $preset): void;

    /**
     * Replicates the items of a freshly-saved budget month onto a new
     * preset. Useful for the "Save current month as preset" button —
     * captures the user's actual structure without having to retype it.
     */
    public function createFromBudget(
        CoreUserInterface $user,
        PersonalFinanceBudgetInterface $budget,
        string $name,
        ?string $description = null,
    ): PersonalFinanceBudgetPresetInterface;

    /**
     * Applies the preset's items to the target budget. Mode `Append`
     * inserts the preset's items on top of existing ones (no dedup).
     * Mode `Replace` clears the budget's items before insertion.
     *
     * Returns the count of items inserted (Replace mode reports
     * inserted count, not net change).
     */
    public function applyToMonth(
        PersonalFinanceBudgetPresetInterface $preset,
        PersonalFinanceBudgetInterface $budget,
        PersonalFinanceBudgetPresetApplyModeEnum $mode,
    ): int;
}
