<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\EventSubscriber;

use Aurora\Module\Platform\Agency\Entity\Agency;
use Aurora\Module\Platform\Service\Entity\Service;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Event\UserAgencyServiceUpdatingEvent;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Hr\EventSubscriber\HrEmployeeSyncListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class HrEmployeeSyncListenerTest extends TestCase
{
    private EmployeeRepository $employeeRepository;
    private HrEmployeeSyncListener $listener;

    protected function setUp(): void
    {
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->listener = new HrEmployeeSyncListener($this->employeeRepository);
    }

    public function testGetSubscribedEventsReturnsExpectedMapping(): void
    {
        self::assertSame(
            [UserAgencyServiceUpdatingEvent::class => 'onUserAgencyServiceUpdating'],
            HrEmployeeSyncListener::getSubscribedEvents(),
        );
    }

    public function testOverridesAgencyAndServiceFromEmployeeWhenUserIsLinked(): void
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 1);

        $agency = new Agency();
        $agency->setName('HQ');
        $service = new Service();
        $service->setName('R&D');

        $employee = $this->createMock(EmployeeInterface::class);
        $employee->method('getAgency')->willReturn($agency);
        $employee->method('getService')->willReturn($service);

        $this->employeeRepository->method('findOneByUser')->willReturn($employee);

        $event = new UserAgencyServiceUpdatingEvent($user, null, null);
        $this->listener->onUserAgencyServiceUpdating($event);

        self::assertSame($agency, $event->getAgency());
        self::assertSame($service, $event->getService());
    }

    public function testDoesNotOverrideAgencyAndServiceWhenUserHasNoEmployee(): void
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 2);

        $initialAgency = new Agency();
        $initialAgency->setName('Initial');
        $initialService = new Service();
        $initialService->setName('Initial');

        $this->employeeRepository->method('findOneByUser')->willReturn(null);

        $event = new UserAgencyServiceUpdatingEvent($user, $initialAgency, $initialService);
        $this->listener->onUserAgencyServiceUpdating($event);

        self::assertSame($initialAgency, $event->getAgency());
        self::assertSame($initialService, $event->getService());
    }
}
