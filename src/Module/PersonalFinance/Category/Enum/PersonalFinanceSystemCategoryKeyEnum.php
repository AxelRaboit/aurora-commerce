<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Enum;

enum PersonalFinanceSystemCategoryKeyEnum: string
{
    case TransferIncome = 'transfer_income';
    case BalanceAdjustment = 'balance_adjustment';

    public function systemKey(): string
    {
        return $this->value;
    }

    public static function transferExpenseKey(int $toWalletId): string
    {
        return 'transfer_expense_'.$toWalletId;
    }

    public static function isTransferExpenseKey(string $key): bool
    {
        return str_starts_with($key, 'transfer_expense_');
    }
}
