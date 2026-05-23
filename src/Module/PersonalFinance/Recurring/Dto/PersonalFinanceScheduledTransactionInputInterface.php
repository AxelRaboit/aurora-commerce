<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;

interface PersonalFinanceScheduledTransactionInputInterface
{
    public function getWalletId(): ?int;

    public function getCategoryId(): ?int;

    public function getType(): PersonalFinanceTransactionTypeEnum;

    public function getAmount(): string;

    public function getDescription(): ?string;

    public function getScheduledDate(): DateTimeImmutable;
}
