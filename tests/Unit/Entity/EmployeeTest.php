<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Hr\Employee\Entity\Employee;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Employee())->getId());
    }

    public function testOptionalFieldsNullByDefault(): void
    {
        $employee = new Employee();

        self::assertNull($employee->getJobTitle());
        self::assertNull($employee->getPhone());
        self::assertNull($employee->getWorkEmail());
        self::assertNull($employee->getHiredAt());
        self::assertNull($employee->getLeftAt());
        self::assertNull($employee->getUser());
        self::assertNull($employee->getService());
        self::assertNull($employee->getAgency());
    }

    public function testNameGettersAndSetters(): void
    {
        $employee = (new Employee())->setFirstName('Jane')->setLastName('Doe');

        self::assertSame('Jane', $employee->getFirstName());
        self::assertSame('Doe', $employee->getLastName());
    }

    public function testGetFullNameCombinesNames(): void
    {
        $employee = (new Employee())->setFirstName('Jane')->setLastName('Doe');

        self::assertSame('Jane Doe', $employee->getFullName());
    }

    public function testJobTitlePhoneAndEmailGettersAndSetters(): void
    {
        $employee = (new Employee())
            ->setJobTitle('Developer')
            ->setPhone('+33 1 23 45 67 89')
            ->setWorkEmail('jane.doe@example.com');

        self::assertSame('Developer', $employee->getJobTitle());
        self::assertSame('+33 1 23 45 67 89', $employee->getPhone());
        self::assertSame('jane.doe@example.com', $employee->getWorkEmail());
    }

    public function testHiredAtAndLeftAtGettersAndSetters(): void
    {
        $hired = new DateTimeImmutable('2026-01-01');
        $left = new DateTimeImmutable('2027-12-31');

        $employee = (new Employee())->setHiredAt($hired)->setLeftAt($left);

        self::assertSame($hired, $employee->getHiredAt());
        self::assertSame($left, $employee->getLeftAt());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $employee = (new Employee())->setUser($user);

        self::assertSame($user, $employee->getUser());
    }

    public function testServiceGetterAndSetter(): void
    {
        $service = new Service();
        $employee = (new Employee())->setService($service);

        self::assertSame($service, $employee->getService());
    }

    public function testAgencyGetterAndSetter(): void
    {
        $agency = new Agency();
        $employee = (new Employee())->setAgency($agency);

        self::assertSame($agency, $employee->getAgency());
    }
}
