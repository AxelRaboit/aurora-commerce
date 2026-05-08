<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CompanyInputFactoryInterface::class)]
class CompanyInputFactory implements CompanyInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): CompanyInputInterface
    {
        return new CompanyInput(
            name: Str::trimFromArray($data, 'name'),
            industry: Str::trimOrNullFromArray($data, 'industry'),
            website: Str::trimOrNullFromArray($data, 'website'),
            phone: Str::trimOrNullFromArray($data, 'phone'),
            address: Str::trimOrNullFromArray($data, 'address'),
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }
}
