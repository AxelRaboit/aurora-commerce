<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Transfer\Dto;

use DateTimeImmutable;

interface PersonalFinanceTransferInputInterface
{
    public function getFromWalletId(): ?int;

    public function getToWalletId(): ?int;

    public function getAmount(): string;

    public function getDate(): DateTimeImmutable;

    public function getDescription(): ?string;
}
