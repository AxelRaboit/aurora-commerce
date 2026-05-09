<?php

declare(strict_types=1);

namespace Aurora\Core\User\Event;

use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Service\Entity\ServiceInterface;
use Aurora\Core\User\Entity\CoreUserInterface;

/**
 * Dispatched by UserManager::updateAgencyAndService() before the values are
 * applied to the User entity. Listeners (e.g. HrEmployeeSyncListener) may
 * override the proposed agency/service — the UserManager uses the event's
 * final values, not the originals.
 *
 * This keeps Core unaware of Hr while letting Hr enforce Employee as the
 * authoritative source for users that have a linked employee profile.
 */
final class UserAgencyServiceUpdatingEvent
{
    public function __construct(
        private readonly CoreUserInterface $user,
        private ?AgencyInterface $agency,
        private ?ServiceInterface $service,
    ) {}

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function getAgency(): ?AgencyInterface
    {
        return $this->agency;
    }

    public function setAgency(?AgencyInterface $agency): void
    {
        $this->agency = $agency;
    }

    public function getService(): ?ServiceInterface
    {
        return $this->service;
    }

    public function setService(?ServiceInterface $service): void
    {
        $this->service = $service;
    }
}
