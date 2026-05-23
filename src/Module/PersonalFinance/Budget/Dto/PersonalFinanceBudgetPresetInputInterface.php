<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

interface PersonalFinanceBudgetPresetInputInterface
{
    public function getName(): string;

    public function getDescription(): ?string;

    /** @return list<PersonalFinanceBudgetPresetItemInputInterface> */
    public function getItems(): array;
}
