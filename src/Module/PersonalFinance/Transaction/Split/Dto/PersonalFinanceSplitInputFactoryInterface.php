<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Dto;

interface PersonalFinanceSplitInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceSplitInputInterface;
}
