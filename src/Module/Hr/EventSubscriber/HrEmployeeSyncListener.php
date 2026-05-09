<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\EventSubscriber;

use Aurora\Core\User\Event\UserAgencyServiceUpdatingEvent;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When a User's agency/service is about to be updated and that User is linked
 * to an Employee, the Employee is the authoritative source: we override the
 * proposed values with those of the Employee so direct User edits cannot
 * diverge from the HR record.
 */
final readonly class HrEmployeeSyncListener implements EventSubscriberInterface
{
    public function __construct(private EmployeeRepository $employeeRepository) {}

    public static function getSubscribedEvents(): array
    {
        return [UserAgencyServiceUpdatingEvent::class => 'onUserAgencyServiceUpdating'];
    }

    public function onUserAgencyServiceUpdating(UserAgencyServiceUpdatingEvent $event): void
    {
        $employee = $this->employeeRepository->findOneByUser($event->getUser());

        if (!$employee instanceof EmployeeInterface) {
            return;
        }

        $event->setAgency($employee->getAgency());
        $event->setService($employee->getService());
    }
}
