<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletInputFactoryInterface::class)]
class PersonalFinanceWalletInputFactory implements PersonalFinanceWalletInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceWalletInputInterface
    {
        $modeValue = Str::trimFromArray($data, 'mode');
        $mode = PersonalFinanceWalletModeEnum::tryFrom($modeValue) ?? PersonalFinanceWalletModeEnum::Simple;

        $startBalance = isset($data['startBalance']) && '' !== mb_trim((string) $data['startBalance'])
            ? (string) $data['startBalance']
            : '0.00';

        return new PersonalFinanceWalletInput(
            name: Str::trimFromArray($data, 'name'),
            startBalance: $startBalance,
            mode: $mode,
            showOnDashboard: (bool) ($data['showOnDashboard'] ?? true),
            position: isset($data['position']) ? (int) $data['position'] : 0,
        );
    }
}
