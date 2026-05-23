<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceCategorizationRuleInput implements PersonalFinanceCategorizationRuleInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'personal_finance.categorization.errors.category_required')]
        #[Assert\Positive(message: 'personal_finance.categorization.errors.category_required')]
        public readonly ?int $categoryId = null,
    ) {}

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
}
