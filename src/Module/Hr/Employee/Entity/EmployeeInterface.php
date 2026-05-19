<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\Service\Entity\ServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface EmployeeInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getFirstName(): string;

    public function setFirstName(string $firstName): static;

    public function getLastName(): string;

    public function setLastName(string $lastName): static;

    public function getFullName(): string;

    public function getJobTitle(): ?string;

    public function setJobTitle(?string $jobTitle): static;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): static;

    public function getWorkEmail(): ?string;

    public function setWorkEmail(?string $workEmail): static;

    public function getHiredAt(): ?DateTimeImmutable;

    public function setHiredAt(?DateTimeImmutable $hiredAt): static;

    public function getLeftAt(): ?DateTimeImmutable;

    public function setLeftAt(?DateTimeImmutable $leftAt): static;

    public function getUser(): ?CoreUserInterface;

    public function setUser(?CoreUserInterface $user): static;

    public function getService(): ?ServiceInterface;

    public function setService(?ServiceInterface $service): static;

    public function getAgency(): ?AgencyInterface;

    public function setAgency(?AgencyInterface $agency): static;
}
