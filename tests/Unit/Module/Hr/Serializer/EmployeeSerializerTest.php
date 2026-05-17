<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Serializer;

use Aurora\Core\Platform\Agency\Entity\Agency;
use Aurora\Core\Platform\Service\Entity\Service;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Serializer\EmployeeSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class EmployeeSerializerTest extends TestCase
{
    private EmployeeSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new EmployeeSerializer();
    }

    private function makeEmployeeMock(
        ?DateTimeImmutable $hiredAt = new DateTimeImmutable('2024-01-15'),
        ?DateTimeImmutable $leftAt = null,
        mixed $user = null,
        mixed $service = null,
        mixed $agency = null,
    ): EmployeeInterface {
        $employee = $this->createMock(EmployeeInterface::class);
        $employee->method('getId')->willReturn(1);
        $employee->method('getFirstName')->willReturn('Jane');
        $employee->method('getLastName')->willReturn('Doe');
        $employee->method('getFullName')->willReturn('Jane Doe');
        $employee->method('getJobTitle')->willReturn('Engineer');
        $employee->method('getPhone')->willReturn('+33600000000');
        $employee->method('getWorkEmail')->willReturn('jane@example.com');
        $employee->method('getHiredAt')->willReturn($hiredAt);
        $employee->method('getLeftAt')->willReturn($leftAt);
        $employee->method('getUser')->willReturn($user);
        $employee->method('getService')->willReturn($service);
        $employee->method('getAgency')->willReturn($agency);
        $employee->method('getCreatedAt')->willReturn(new DateTimeImmutable('2024-01-01'));
        $employee->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2024-01-02'));

        return $employee;
    }

    public function testSerializeIncludesAllExpectedKeys(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock());

        self::assertArrayHasKey('id', $payload);
        self::assertArrayHasKey('firstName', $payload);
        self::assertArrayHasKey('lastName', $payload);
        self::assertArrayHasKey('fullName', $payload);
        self::assertArrayHasKey('jobTitle', $payload);
        self::assertArrayHasKey('phone', $payload);
        self::assertArrayHasKey('workEmail', $payload);
        self::assertArrayHasKey('hiredAt', $payload);
        self::assertArrayHasKey('leftAt', $payload);
        self::assertArrayHasKey('user', $payload);
        self::assertArrayHasKey('service', $payload);
        self::assertArrayHasKey('agency', $payload);
        self::assertArrayHasKey('createdAt', $payload);
        self::assertArrayHasKey('updatedAt', $payload);
    }

    public function testSerializeScalarFields(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock());

        self::assertSame(1, $payload['id']);
        self::assertSame('Jane', $payload['firstName']);
        self::assertSame('Doe', $payload['lastName']);
        self::assertSame('Jane Doe', $payload['fullName']);
        self::assertSame('Engineer', $payload['jobTitle']);
        self::assertSame('+33600000000', $payload['phone']);
        self::assertSame('jane@example.com', $payload['workEmail']);
    }

    public function testHiredAtAndLeftAtFormattedAsYmd(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock(
            hiredAt: new DateTimeImmutable('2024-03-10'),
            leftAt: new DateTimeImmutable('2025-06-30'),
        ));

        self::assertSame('2024-03-10', $payload['hiredAt']);
        self::assertSame('2025-06-30', $payload['leftAt']);
    }

    public function testLeftAtIsNullWhenNotSet(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock(leftAt: null));

        self::assertNull($payload['leftAt']);
    }

    public function testUserIsNullWhenNotLinked(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock(user: null));

        self::assertNull($payload['user']);
    }

    public function testUserContainsIdNameEmailWhenLinked(): void
    {
        $user = new User();
        $user->setEmail('jane@example.com')->setName('Jane Doe');
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 42);

        $payload = $this->serializer->serialize($this->makeEmployeeMock(user: $user));

        self::assertSame(['id' => 42, 'name' => 'Jane Doe', 'email' => 'jane@example.com'], $payload['user']);
    }

    public function testServiceIsNullWhenNotLinked(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock(service: null));

        self::assertNull($payload['service']);
    }

    public function testServiceContainsIdAndNameWhenLinked(): void
    {
        $service = new Service();
        $service->setName('R&D');
        (new ReflectionProperty(Service::class, 'id'))->setValue($service, 10);

        $payload = $this->serializer->serialize($this->makeEmployeeMock(service: $service));

        self::assertSame(['id' => 10, 'name' => 'R&D'], $payload['service']);
    }

    public function testAgencyIsNullWhenNotLinked(): void
    {
        $payload = $this->serializer->serialize($this->makeEmployeeMock(agency: null));

        self::assertNull($payload['agency']);
    }

    public function testAgencyContainsIdAndNameWhenLinked(): void
    {
        $agency = new Agency();
        $agency->setName('HQ');
        (new ReflectionProperty(Agency::class, 'id'))->setValue($agency, 7);

        $payload = $this->serializer->serialize($this->makeEmployeeMock(agency: $agency));

        self::assertSame(['id' => 7, 'name' => 'HQ'], $payload['agency']);
    }
}
