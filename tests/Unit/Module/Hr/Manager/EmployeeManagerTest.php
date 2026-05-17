<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Manager;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\Service\Repository\ServiceRepository;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Hr\Employee\Dto\EmployeeInputInterface;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Manager\EmployeeManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class EmployeeManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EmployeeManager $manager;

    /** @var list<array{string, string, string, int|null, array<string, mixed>|null}> */
    private array $auditLogs = [];

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->auditLogs = [];

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new EmployeeManager(
            $this->entityManager,
            $this->createStub(UserRepository::class),
            $this->createStub(ServiceRepository::class),
            $this->createStub(AgencyRepository::class),
            $auditLogger,
        );
    }

    private function makeInput(
        string $firstName = 'Jane',
        string $lastName = 'Doe',
        ?string $jobTitle = null,
        ?string $phone = null,
        ?string $workEmail = null,
        ?string $hiredAt = null,
        ?string $leftAt = null,
        ?int $userId = null,
        ?int $serviceId = null,
        ?int $agencyId = null,
    ): EmployeeInputInterface {
        $input = $this->createStub(EmployeeInputInterface::class);
        $input->method('getFirstName')->willReturn($firstName);
        $input->method('getLastName')->willReturn($lastName);
        $input->method('getJobTitle')->willReturn($jobTitle);
        $input->method('getPhone')->willReturn($phone);
        $input->method('getWorkEmail')->willReturn($workEmail);
        $input->method('getHiredAt')->willReturn($hiredAt);
        $input->method('getLeftAt')->willReturn($leftAt);
        $input->method('getUserId')->willReturn($userId);
        $input->method('getServiceId')->willReturn($serviceId);
        $input->method('getAgencyId')->willReturn($agencyId);

        return $input;
    }

    private function makeEmployee(int $id = 1, string $firstName = 'Jane', string $lastName = 'Doe'): Employee
    {
        $employee = new Employee();
        $employee->setFirstName($firstName)->setLastName($lastName);
        (new ReflectionProperty(Employee::class, 'id'))->setValue($employee, $id);

        return $employee;
    }

    public function testCreateCallsPersistAndFlushAndAuditsCreated(): void
    {
        $persisted = [];
        $this->entityManager->expects(self::atLeastOnce())->method('persist')->willReturnCallback(
            function (object $entity) use (&$persisted): void { $persisted[] = $entity; },
        );
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $employee = $this->manager->create($this->makeInput());

        $employeesPersisted = array_filter($persisted, static fn (object $entity): bool => $entity instanceof EmployeeInterface);
        self::assertCount(1, $employeesPersisted);
        self::assertInstanceOf(EmployeeInterface::class, $employee);
    }

    public function testCreateLogsWithModuleHrAndActionEmployeeCreated(): void
    {
        $auditLogs = [];
        $this->entityManager->method('persist')->willReturnCallback(
            function (object $entity) use (&$auditLogs): void {
                $auditLogs[] = $entity;
            },
        );

        $this->manager->create($this->makeInput(firstName: 'Alice', lastName: 'Smith'));

        $auditLogEntries = array_filter($auditLogs, static fn (object $entity): bool => !$entity instanceof EmployeeInterface);
        self::assertNotEmpty($auditLogEntries);
    }

    public function testUpdateCallsFlushAndAuditsUpdated(): void
    {
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $employee = $this->makeEmployee();
        $this->manager->update($employee, $this->makeInput(firstName: 'Updated'));

        self::assertSame('Updated', $employee->getFirstName());
    }

    public function testDeleteCallsRemoveAndFlushAndAuditsDeleted(): void
    {
        $employee = $this->makeEmployee();

        $this->entityManager->expects(self::once())->method('remove')->with($employee);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($employee);
    }

    public function testSyncUserFromEmployeePropagatesServiceAndAgencyToUser(): void
    {
        $agency = new Agency();
        $agency->setName('HQ');
        (new ReflectionProperty(Agency::class, 'id'))->setValue($agency, 5);

        $service = new Service();
        $service->setName('R&D');
        (new ReflectionProperty(Service::class, 'id'))->setValue($service, 3);

        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 10);

        $agencyRepository = $this->createStub(AgencyRepository::class);
        $agencyRepository->method('find')->willReturn($agency);

        $serviceRepository = $this->createStub(ServiceRepository::class);
        $serviceRepository->method('find')->willReturn($service);

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('find')->willReturn($user);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $manager = new EmployeeManager(
            $this->entityManager,
            $userRepository,
            $serviceRepository,
            $agencyRepository,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($this->createStub(Connection::class)),
                $this->createStub(SettingRepository::class),
            ),
        );

        $employee = $manager->create($this->makeInput(userId: 10, serviceId: 3, agencyId: 5));

        self::assertSame($service, $employee->getUser()?->getService());
        self::assertSame($agency, $employee->getUser()?->getAgency());
    }

    public function testAuditPayloadContainsExpectedKeys(): void
    {
        $agency = new Agency();
        $agency->setName('HQ');
        (new ReflectionProperty(Agency::class, 'id'))->setValue($agency, 5);

        $service = new Service();
        $service->setName('R&D');
        (new ReflectionProperty(Service::class, 'id'))->setValue($service, 3);

        $employee = $this->makeEmployee();
        $employee->setJobTitle('Dev')->setAgency($agency)->setService($service);

        $auditLogs = [];
        $this->entityManager->method('persist')->willReturnCallback(
            function (object $entity) use (&$auditLogs): void {
                $auditLogs[] = $entity;
            },
        );

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $manager = new EmployeeManager(
            $this->entityManager,
            $this->createStub(UserRepository::class),
            $this->createStub(ServiceRepository::class),
            $this->createStub(AgencyRepository::class),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($this->createStub(Connection::class)),
                $this->createStub(SettingRepository::class),
            ),
        );

        $manager->delete($employee);

        $auditLogEntries = array_filter($auditLogs, static fn (object $entity): bool => !$entity instanceof EmployeeInterface);
        self::assertNotEmpty($auditLogEntries);
    }
}
