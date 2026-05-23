<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceSplitInput implements PersonalFinanceSplitInputInterface
{
    /**
     * @param list<string>                   $tags
     * @param list<PersonalFinanceSplitPart> $parts
     */
    public function __construct(
        #[Assert\NotNull]
        public readonly PersonalFinanceTransactionTypeEnum $type = PersonalFinanceTransactionTypeEnum::Expense,
        #[Assert\NotNull(message: 'personal_finance.splits.errors.date_required')]
        public readonly ?DateTimeImmutable $date = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
        public readonly array $tags = [],
        #[Assert\Count(min: 2, minMessage: 'personal_finance.splits.errors.min_parts')]
        #[Assert\Valid]
        public readonly array $parts = [],
    ) {}

    public function getType(): PersonalFinanceTransactionTypeEnum
    {
        return $this->type;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date ?? new DateTimeImmutable('today');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @return list<PersonalFinanceSplitPart> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
