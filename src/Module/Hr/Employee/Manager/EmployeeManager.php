<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Hr\Employee\Dto\EmployeeInputInterface;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Platform\Agency\Repository\AgencyRepository;
use Aurora\Module\Platform\Service\Repository\ServiceRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(EmployeeManagerInterface::class)]
class EmployeeManager implements EmployeeManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly ServiceRepository $serviceRepository,
        protected readonly AgencyRepository $agencyRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(EmployeeInputInterface $input): EmployeeInterface
    {
        $employee = $this->createEmployee();
        $this->applyInput($employee, $input);
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        $this->auditCreated($employee);

        return $employee;
    }

    public function update(EmployeeInterface $employee, EmployeeInputInterface $input): void
    {
        $this->applyInput($employee, $input);
        $this->entityManager->flush();

        $this->auditUpdated($employee);
    }

    public function delete(EmployeeInterface $employee): void
    {
        $this->auditDeleted($employee);

        $this->entityManager->remove($employee);
        $this->entityManager->flush();
    }

    protected function createEmployee(): EmployeeInterface
    {
        return new Employee();
    }

    protected function applyInput(EmployeeInterface $employee, EmployeeInputInterface $input): void
    {
        $employee->setFirstName($input->getFirstName());
        $employee->setLastName($input->getLastName());
        $employee->setJobTitle($input->getJobTitle());
        $employee->setPhone($input->getPhone());
        $employee->setWorkEmail($input->getWorkEmail());
        $employee->setHiredAt(null !== $input->getHiredAt() ? new DateTimeImmutable($input->getHiredAt()) : null);
        $employee->setLeftAt(null !== $input->getLeftAt() ? new DateTimeImmutable($input->getLeftAt()) : null);
        $employee->setUser(null !== $input->getUserId() ? $this->userRepository->find($input->getUserId()) : null);
        $employee->setService(null !== $input->getServiceId() ? $this->serviceRepository->find($input->getServiceId()) : null);
        $employee->setAgency(null !== $input->getAgencyId() ? $this->agencyRepository->find($input->getAgencyId()) : null);

        $this->syncUserFromEmployee($employee);
    }

    protected function syncUserFromEmployee(EmployeeInterface $employee): void
    {
        if (!$employee->getUser() instanceof CoreUserInterface) {
            return;
        }

        $employee->getUser()->setService($employee->getService());
        $employee->getUser()->setAgency($employee->getAgency());
    }

    protected function auditCreated(EmployeeInterface $employee): void
    {
        $this->auditLogger->log('hr', 'employee.created', 'Employee', $employee->getId(), $this->auditPayload($employee));
    }

    protected function auditUpdated(EmployeeInterface $employee): void
    {
        $this->auditLogger->log('hr', 'employee.updated', 'Employee', $employee->getId(), $this->auditPayload($employee));
    }

    protected function auditDeleted(EmployeeInterface $employee): void
    {
        $this->auditLogger->log('hr', 'employee.deleted', 'Employee', $employee->getId(), $this->auditPayload($employee));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(EmployeeInterface $employee): array
    {
        return [
            'fullName' => $employee->getFullName(),
            'jobTitle' => $employee->getJobTitle(),
            'serviceId' => $employee->getService()?->getId(),
            'agencyId' => $employee->getAgency()?->getId(),
            'userId' => $employee->getUser()?->getId(),
        ];
    }
}
