<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;

interface PersonalFinanceWalletInputInterface
{
    public function getName(): string;

    public function getStartBalance(): string;

    public function getMode(): PersonalFinanceWalletModeEnum;

    public function isShowOnDashboard(): bool;

    public function getPosition(): int;
}
