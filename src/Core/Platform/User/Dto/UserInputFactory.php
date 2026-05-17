<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\User\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserInputFactoryInterface::class)]
class UserInputFactory implements UserInputFactoryInterface
{
    public function fromArray(array $data): UserInputInterface
    {
        $password = Str::trimOrNullFromArray($data, 'password');
        $managerId = $data['managerId'] ?? null;
        $agencyId = $data['agencyId'] ?? null;
        $serviceId = $data['serviceId'] ?? null;

        return new UserInput(
            name: Str::trimFromArray($data, 'name'),
            email: Str::trimFromArray($data, 'email'),
            role: Str::trimFromArray($data, 'role'),
            locale: Str::trimFromArray($data, 'locale', 'fr') ?: 'fr',
            password: '' === $password ? null : $password,
            managerId: is_numeric($managerId) ? (int) $managerId : null,
            agencyId: is_numeric($agencyId) ? (int) $agencyId : null,
            serviceId: is_numeric($serviceId) ? (int) $serviceId : null,
        );
    }
}
