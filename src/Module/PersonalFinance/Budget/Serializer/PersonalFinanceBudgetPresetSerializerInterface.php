<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;

interface PersonalFinanceBudgetPresetSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceBudgetPresetInterface $preset): array;

    /** @return array<string, mixed> */
    public function serializeItem(PersonalFinanceBudgetPresetItemInterface $item): array;
}
