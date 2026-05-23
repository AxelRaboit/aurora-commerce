<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Dto;

interface PersonalFinanceCategorizationRuleInputInterface
{
    public function getCategoryId(): ?int;
}
