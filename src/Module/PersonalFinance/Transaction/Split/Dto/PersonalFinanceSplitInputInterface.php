<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;

interface PersonalFinanceSplitInputInterface
{
    public function getType(): PersonalFinanceTransactionTypeEnum;

    public function getDate(): DateTimeImmutable;

    public function getDescription(): ?string;

    /** @return list<string> */
    public function getTags(): array;

    /** @return list<PersonalFinanceSplitPart> */
    public function getParts(): array;
}
