<?php

declare(strict_types=1);

namespace App\Module\Crm\Company\Serializer;

use App\Module\Crm\Company\Entity\Company;
use DateTimeInterface;

final readonly class CompanySerializer
{
    public function serialize(Company $company): array
    {
        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'industry' => $company->getIndustry(),
            'website' => $company->getWebsite(),
            'phone' => $company->getPhone(),
            'address' => $company->getAddress(),
            'notes' => $company->getNotes(),
            'createdAt' => $company->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $company->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
