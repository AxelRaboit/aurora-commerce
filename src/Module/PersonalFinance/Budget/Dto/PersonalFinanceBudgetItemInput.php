<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceBudgetItemInput implements PersonalFinanceBudgetItemInputInterface
{
    public function __construct(
        #[Assert\NotNull]
        public readonly PersonalFinanceBudgetSectionEnum $section = PersonalFinanceBudgetSectionEnum::Expenses,
        #[Assert\NotBlank(message: 'personal_finance.budget.errors.label_required')]
        #[Assert\Length(max: 120)]
        public readonly string $label = '',
        #[Assert\NotBlank(message: 'personal_finance.budget.errors.planned_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.budget.errors.planned_format',
        )]
        public readonly string $plannedAmount = '0.00',
        #[Assert\Regex(
            pattern: '/^-?\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.budget.errors.carried_format',
        )]
        public readonly string $carriedOver = '0.00',
        public readonly ?int $categoryId = null,
        #[Assert\PositiveOrZero]
        public readonly int $position = 0,
        #[Assert\Length(max: 255)]
        public readonly ?string $notes = null,
        public readonly bool $repeatNextMonth = false,
    ) {}

    public function getSection(): PersonalFinanceBudgetSectionEnum
    {
        return $this->section;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPlannedAmount(): string
    {
        return $this->plannedAmount;
    }

    public function getCarriedOver(): string
    {
        return $this->carriedOver;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function repeatsNextMonth(): bool
    {
        return $this->repeatNextMonth;
    }
}
