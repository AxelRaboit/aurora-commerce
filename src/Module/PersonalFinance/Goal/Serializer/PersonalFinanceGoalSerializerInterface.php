<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Serializer;

use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;

interface PersonalFinanceGoalSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceGoalInterface $goal): array;
}
