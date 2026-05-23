<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Serializer;

use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;

interface PersonalFinanceCategorizationRuleSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceCategorizationRuleInterface $rule): array;
}
