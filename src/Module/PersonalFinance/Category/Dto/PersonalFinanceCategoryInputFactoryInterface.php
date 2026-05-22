<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Dto;

interface PersonalFinanceCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceCategoryInputInterface;
}
