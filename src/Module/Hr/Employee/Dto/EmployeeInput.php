<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EmployeeInput implements EmployeeInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.employees.errors.first_name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $firstName = '',
        #[Assert\NotBlank(message: 'backend.employees.errors.last_name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $lastName = '',
        #[Assert\Length(max: 150)]
        public readonly ?string $jobTitle = null,
        #[Assert\Length(max: 30)]
        public readonly ?string $phone = null,
        #[Assert\Email(message: 'backend.employees.errors.work_email_invalid')]
        #[Assert\Length(max: 180)]
        public readonly ?string $workEmail = null,
        public readonly ?string $hiredAt = null,
        public readonly ?string $leftAt = null,
        #[Assert\Positive]
        public readonly ?int $userId = null,
        #[Assert\Positive]
        public readonly ?int $serviceId = null,
        #[Assert\Positive]
        public readonly ?int $agencyId = null,
    ) {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getWorkEmail(): ?string
    {
        return $this->workEmail;
    }

    public function getHiredAt(): ?string
    {
        return $this->hiredAt;
    }

    public function getLeftAt(): ?string
    {
        return $this->leftAt;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    public function getAgencyId(): ?int
    {
        return $this->agencyId;
    }
}
