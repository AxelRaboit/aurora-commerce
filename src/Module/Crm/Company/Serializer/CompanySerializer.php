<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Serializer;

use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CompanySerializerInterface::class)]
class CompanySerializer implements CompanySerializerInterface
{
    public function serialize(CompanyInterface $company): array
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
