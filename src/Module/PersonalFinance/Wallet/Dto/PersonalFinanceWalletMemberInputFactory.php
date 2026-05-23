<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletMemberInputFactoryInterface::class)]
class PersonalFinanceWalletMemberInputFactory implements PersonalFinanceWalletMemberInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceWalletMemberInputInterface
    {
        $roleValue = Str::trimFromArray($data, 'role');
        $role = PersonalFinanceWalletRoleEnum::tryFrom($roleValue) ?? PersonalFinanceWalletRoleEnum::Viewer;

        return new PersonalFinanceWalletMemberInput(role: $role);
    }

    /**
     * Convenience for callers that already have the role enum (auto-attach
     * Owner on wallet creation, invitation acceptance) — keeps them from
     * having to round-trip through a fake JSON payload.
     */
    public function fromRole(PersonalFinanceWalletRoleEnum $role): PersonalFinanceWalletMemberInputInterface
    {
        return new PersonalFinanceWalletMemberInput(role: $role);
    }
}
