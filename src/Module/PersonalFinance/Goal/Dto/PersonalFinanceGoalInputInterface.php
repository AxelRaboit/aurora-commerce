<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

use DateTimeImmutable;

interface PersonalFinanceGoalInputInterface
{
    public function getName(): string;

    public function getTargetAmount(): string;

    public function getWalletId(): ?int;

    public function getCategoryId(): ?int;

    public function getDeadline(): ?DateTimeImmutable;

    public function getColor(): ?string;
}
