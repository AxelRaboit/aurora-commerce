<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Dto;

interface EmployeeInputInterface
{
    public function getFirstName(): string;

    public function getLastName(): string;

    public function getJobTitle(): ?string;

    public function getPhone(): ?string;

    public function getWorkEmail(): ?string;

    public function getHiredAt(): ?string;

    public function getLeftAt(): ?string;

    public function getUserId(): ?int;

    public function getServiceId(): ?int;

    public function getAgencyId(): ?int;
}
