<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Enum;

/**
 * How the auto-sync subscriber computes a category-tracking goal's
 * `savedAmount`. Picked per-goal at creation so two goals on the same
 * category can have different semantics.
 *
 * - `IncomeOnly`: only income transactions in the category add to the
 *   goal. Natural fit for savings goals ("Vacances été") — every income
 *   tagged that category is money "going into" the goal.
 * - `ExpenseOnly`: only expense transactions add. Natural fit for
 *   budget-consumed goals ("Loisirs ce mois") — every expense fills
 *   the bar toward the target.
 *
 * An earlier `AbsoluteSum` variant (sum both directions, Spendly legacy)
 * was dropped because it almost always surprised users — a refund tagged
 * in an expense category would silently inflate the goal. The 2-case
 * enum forces an explicit semantic at creation.
 */
enum PersonalFinanceGoalTrackingModeEnum: string
{
    case IncomeOnly = 'income_only';
    case ExpenseOnly = 'expense_only';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
