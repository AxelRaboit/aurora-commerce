<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Serializer;

use Aurora\Core\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Platform\Service\Entity\ServiceInterface;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(EmployeeSerializerInterface::class)]
class EmployeeSerializer implements EmployeeSerializerInterface
{
    public function serialize(EmployeeInterface $employee): array
    {
        return [
            'id' => $employee->getId(),
            'firstName' => $employee->getFirstName(),
            'lastName' => $employee->getLastName(),
            'fullName' => $employee->getFullName(),
            'jobTitle' => $employee->getJobTitle(),
            'phone' => $employee->getPhone(),
            'workEmail' => $employee->getWorkEmail(),
            'hiredAt' => $employee->getHiredAt()?->format('Y-m-d'),
            'leftAt' => $employee->getLeftAt()?->format('Y-m-d'),
            'user' => $employee->getUser() instanceof CoreUserInterface ? [
                'id' => $employee->getUser()->getId(),
                'name' => $employee->getUser()->getName(),
                'email' => $employee->getUser()->getEmail(),
            ] : null,
            'service' => $employee->getService() instanceof ServiceInterface ? [
                'id' => $employee->getService()->getId(),
                'name' => $employee->getService()->getName(),
            ] : null,
            'agency' => $employee->getAgency() instanceof AgencyInterface ? [
                'id' => $employee->getAgency()->getId(),
                'name' => $employee->getAgency()->getName(),
            ] : null,
            'createdAt' => $employee->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $employee->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
