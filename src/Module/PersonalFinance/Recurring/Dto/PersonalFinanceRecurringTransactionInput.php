<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceRecurringTransactionInput implements PersonalFinanceRecurringTransactionInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'personal_finance.recurring.errors.wallet_required')]
        #[Assert\Positive(message: 'personal_finance.recurring.errors.wallet_required')]
        public readonly ?int $walletId = null,
        public readonly ?int $categoryId = null,
        #[Assert\NotNull]
        public readonly PersonalFinanceTransactionTypeEnum $type = PersonalFinanceTransactionTypeEnum::Expense,
        #[Assert\NotBlank(message: 'personal_finance.recurring.errors.amount_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.recurring.errors.amount_format',
        )]
        #[Assert\GreaterThan(value: '0', message: 'personal_finance.recurring.errors.amount_positive')]
        public readonly string $amount = '0.00',
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
        #[Assert\Range(notInRangeMessage: 'personal_finance.recurring.errors.day_of_month_range', min: 1, max: 28)]
        public readonly int $dayOfMonth = 1,
        public readonly bool $active = true,
    ) {}

    public function getWalletId(): ?int
    {
        return $this->walletId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getType(): PersonalFinanceTransactionTypeEnum
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDayOfMonth(): int
    {
        return $this->dayOfMonth;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
