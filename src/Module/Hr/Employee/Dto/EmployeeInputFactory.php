<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(EmployeeInputFactoryInterface::class)]
class EmployeeInputFactory implements EmployeeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): EmployeeInputInterface
    {
        return new EmployeeInput(
            firstName: Str::trimFromArray($data, 'firstName'),
            lastName: Str::trimFromArray($data, 'lastName'),
            jobTitle: Str::trimOrNullFromArray($data, 'jobTitle'),
            phone: Str::trimOrNullFromArray($data, 'phone'),
            workEmail: Str::trimOrNullFromArray($data, 'workEmail'),
            hiredAt: isset($data['hiredAt']) && '' !== (string) $data['hiredAt'] ? (string) $data['hiredAt'] : null,
            leftAt: isset($data['leftAt']) && '' !== (string) $data['leftAt'] ? (string) $data['leftAt'] : null,
            userId: isset($data['userId']) && '' !== (string) $data['userId'] ? (int) $data['userId'] : null,
            serviceId: isset($data['serviceId']) && '' !== (string) $data['serviceId'] ? (int) $data['serviceId'] : null,
            agencyId: isset($data['agencyId']) && '' !== (string) $data['agencyId'] ? (int) $data['agencyId'] : null,
        );
    }
}
