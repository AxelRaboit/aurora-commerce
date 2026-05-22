<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceTransactionInput implements PersonalFinanceTransactionInputInterface
{
    /** @param list<string> $tags */
    public function __construct(
        #[Assert\NotNull]
        public readonly PersonalFinanceTransactionTypeEnum $type = PersonalFinanceTransactionTypeEnum::Expense,
        #[Assert\NotBlank(message: 'personal_finance.transactions.errors.amount_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.transactions.errors.amount_format',
        )]
        public readonly string $amount = '0.00',
        #[Assert\NotNull(message: 'personal_finance.transactions.errors.date_required')]
        public readonly ?DateTimeImmutable $date = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
        public readonly ?int $categoryId = null,
        public readonly array $tags = [],
    ) {}

    public function getType(): PersonalFinanceTransactionTypeEnum
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date ?? new DateTimeImmutable('today');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags;
    }
}
