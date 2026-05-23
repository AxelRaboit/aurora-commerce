<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Dto;

interface PersonalFinanceCategorizationRuleInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceCategorizationRuleInputInterface;
}
