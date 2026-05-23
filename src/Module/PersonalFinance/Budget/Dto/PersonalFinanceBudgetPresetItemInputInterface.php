<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;

interface PersonalFinanceBudgetPresetItemInputInterface
{
    public function getSection(): PersonalFinanceBudgetSectionEnum;

    public function getLabel(): string;

    public function getPlannedAmount(): string;

    public function getCategoryId(): ?int;

    public function getPosition(): int;

    public function getNotes(): ?string;
}
