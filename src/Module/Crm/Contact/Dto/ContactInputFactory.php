<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactInputFactoryInterface::class)]
class ContactInputFactory implements ContactInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ContactInputInterface
    {
        return new ContactInput(
            firstName: Str::trimFromArray($data, 'firstName'),
            lastName: Str::trimFromArray($data, 'lastName'),
            email: Str::trimOrNullFromArray($data, 'email'),
            phone: Str::trimOrNullFromArray($data, 'phone'),
            companyId: isset($data['companyId']) && '' !== (string) $data['companyId'] ? (int) $data['companyId'] : null,
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }
}
