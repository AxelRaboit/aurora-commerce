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
 * - `AbsoluteSum`: both income and expense contribute as |amount|
 *   (Spendly-compatible legacy). Kept for backwards compatibility on
 *   existing goals — rarely the right choice for new ones.
 */
enum PersonalFinanceGoalTrackingModeEnum: string
{
    case IncomeOnly = 'income_only';
    case ExpenseOnly = 'expense_only';
    case AbsoluteSum = 'absolute_sum';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
