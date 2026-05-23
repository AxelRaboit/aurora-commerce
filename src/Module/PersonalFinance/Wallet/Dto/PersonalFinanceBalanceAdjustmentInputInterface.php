<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use DateTimeImmutable;

interface PersonalFinanceBalanceAdjustmentInputInterface
{
    public function getNewBalance(): string;

    public function getDate(): DateTimeImmutable;

    public function getDescription(): ?string;
}
