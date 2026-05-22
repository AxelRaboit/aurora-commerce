<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceWalletInput implements PersonalFinanceWalletInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.wallets.errors.name_required')]
        #[Assert\Length(max: 120)]
        public readonly string $name = '',
        #[Assert\NotBlank(message: 'personal_finance.wallets.errors.start_balance_required')]
        #[Assert\Regex(
            pattern: '/^-?\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.wallets.errors.start_balance_format',
        )]
        public readonly string $startBalance = '0.00',
        #[Assert\NotNull]
        public readonly PersonalFinanceWalletModeEnum $mode = PersonalFinanceWalletModeEnum::Simple,
        public readonly bool $showOnDashboard = true,
        #[Assert\GreaterThanOrEqual(0)]
        public readonly int $position = 0,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartBalance(): string
    {
        return $this->startBalance;
    }

    public function getMode(): PersonalFinanceWalletModeEnum
    {
        return $this->mode;
    }

    public function isShowOnDashboard(): bool
    {
        return $this->showOnDashboard;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
