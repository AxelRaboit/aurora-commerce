<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;

interface PersonalFinanceWalletBalanceServiceInterface
{
    /**
     * Current balance = startBalance + Σincome − Σexpense over the
     * full history of the wallet. Returned as a 2-decimal bcmath string.
     */
    public function currentBalance(PersonalFinanceWalletInterface $wallet): string;

    /**
     * Bulk variant : returns `{walletId => currentBalance}` for every
     * wallet in `$wallets`, using a single SQL aggregate. Wallets are
     * always present in the result (`'0.00'` when no transactions).
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return array<int, string>
     */
    public function currentBalances(array $wallets): array;

    /**
     * Balance at the end of the given month (sum of all transactions
     * with date in [first day, last day of month]). 2-decimal string.
     */
    public function monthlyBalance(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): string;

    /**
     * Balance at the very start of the given month (startBalance +
     * net flow strictly before the first day). 2-decimal string.
     */
    public function rollingStartBalance(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): string;

    /**
     * Composite snapshot for a wallet + month — returns the 3 balances
     * in one call. Convenience method for the wallet detail view.
     *
     * @return array{current: string, month: string, rollingStart: string}
     */
    public function snapshot(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): array;
}
