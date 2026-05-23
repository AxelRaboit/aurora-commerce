<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Stateless balance calculations over a wallet's full transaction
 * history. All arithmetic uses bcmath strings to avoid float drift
 * (cf. {@see PersonalFinanceTransactionRepository::netFlow}).
 */
#[AsAlias(PersonalFinanceWalletBalanceServiceInterface::class)]
class PersonalFinanceWalletBalanceService implements PersonalFinanceWalletBalanceServiceInterface
{
    public function __construct(
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
    ) {}

    public function currentBalance(PersonalFinanceWalletInterface $wallet): string
    {
        return bcadd($wallet->getStartBalance(), $this->transactionRepository->netFlow($wallet), 2);
    }

    public function monthlyBalance(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): string
    {
        $start = $this->firstDayOf($month);
        $end = $this->firstDayOf($month->modify('first day of next month'));

        $rollingStart = $this->rollingStartBalance($wallet, $month);
        $monthFlow = $this->transactionRepository->netFlow($wallet, $start, $end);

        return bcadd($rollingStart, $monthFlow, 2);
    }

    public function rollingStartBalance(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): string
    {
        $start = $this->firstDayOf($month);

        return bcadd($wallet->getStartBalance(), $this->transactionRepository->netFlow($wallet, null, $start), 2);
    }

    /**
     * @return array{current: string, month: string, rollingStart: string}
     */
    public function snapshot(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): array
    {
        return [
            'current' => $this->currentBalance($wallet),
            'month' => $this->monthlyBalance($wallet, $month),
            'rollingStart' => $this->rollingStartBalance($wallet, $month),
        ];
    }

    protected function firstDayOf(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->modify('first day of this month')->setTime(0, 0);
    }
}
