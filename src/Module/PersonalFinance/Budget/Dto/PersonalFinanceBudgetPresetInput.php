<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceBudgetPresetInput implements PersonalFinanceBudgetPresetInputInterface
{
    /**
     * @param list<PersonalFinanceBudgetPresetItemInputInterface> $items
     */
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.budget_presets.errors.name_required')]
        #[Assert\Length(max: 120)]
        public readonly string $name = '',
        #[Assert\Length(max: 500)]
        public readonly ?string $description = null,
        #[Assert\Valid]
        public readonly array $items = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return list<PersonalFinanceBudgetPresetItemInputInterface> */
    public function getItems(): array
    {
        return $this->items;
    }
}
