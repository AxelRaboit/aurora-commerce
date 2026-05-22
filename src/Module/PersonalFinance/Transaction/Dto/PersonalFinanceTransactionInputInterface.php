<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;

interface PersonalFinanceTransactionInputInterface
{
    public function getType(): PersonalFinanceTransactionTypeEnum;

    public function getAmount(): string;

    public function getDate(): DateTimeImmutable;

    public function getDescription(): ?string;

    public function getCategoryId(): ?int;

    /** @return list<string> */
    public function getTags(): array;
}
