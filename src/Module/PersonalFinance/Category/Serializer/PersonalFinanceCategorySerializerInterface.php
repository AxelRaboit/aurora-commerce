<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Serializer;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;

interface PersonalFinanceCategorySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceCategoryInterface $category): array;
}
