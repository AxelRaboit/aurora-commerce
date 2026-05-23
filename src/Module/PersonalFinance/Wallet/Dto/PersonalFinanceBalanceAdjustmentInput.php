<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceBalanceAdjustmentInput implements PersonalFinanceBalanceAdjustmentInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.balance_adjustment.errors.new_balance_required')]
        #[Assert\Regex(
            pattern: '/^-?\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.balance_adjustment.errors.new_balance_format',
        )]
        public readonly string $newBalance = '0.00',
        #[Assert\NotNull(message: 'personal_finance.balance_adjustment.errors.date_required')]
        public readonly ?DateTimeImmutable $date = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
    ) {}

    public function getNewBalance(): string
    {
        return $this->newBalance;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date ?? new DateTimeImmutable('today');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
