<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceCategoryInput implements PersonalFinanceCategoryInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.categories.errors.name_required')]
        #[Assert\Length(max: 120)]
        public readonly string $name = '',
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
