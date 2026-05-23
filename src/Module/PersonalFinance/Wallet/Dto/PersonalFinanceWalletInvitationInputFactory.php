<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletInvitationInputFactoryInterface::class)]
class PersonalFinanceWalletInvitationInputFactory implements PersonalFinanceWalletInvitationInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceWalletInvitationInputInterface
    {
        $roleValue = Str::trimOrNullFromArray($data, 'role');

        return new PersonalFinanceWalletInvitationInput(
            email: Str::trimFromArray($data, 'email'),
            role: null !== $roleValue ? PersonalFinanceWalletRoleEnum::tryFrom($roleValue) : null,
        );
    }
}
