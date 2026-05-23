<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Enum;

/**
 * Buckets that group budget items inside a monthly budget. Mirrors
 * Spendly's BudgetSection enum.
 */
enum PersonalFinanceBudgetSectionEnum: string
{
    case Income = 'income';
    case Savings = 'savings';
    case Bills = 'bills';
    case Expenses = 'expenses';
    case Debt = 'debt';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * True when the section represents money flowing into the wallet
     * (income only — every other section is a planned outflow).
     */
    public function isInflow(): bool
    {
        return self::Income === $this;
    }
}
