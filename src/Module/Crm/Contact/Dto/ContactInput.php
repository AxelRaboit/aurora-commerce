<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactInput implements ContactInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'crm.contacts.errors.first_name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $firstName = '',
        #[Assert\NotBlank(message: 'crm.contacts.errors.last_name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $lastName = '',
        #[Assert\Email(message: 'crm.contacts.errors.email_invalid')]
        #[Assert\Length(max: 180)]
        public readonly ?string $email = null,
        #[Assert\Length(max: 50)]
        public readonly ?string $phone = null,
        #[Assert\Positive]
        public readonly ?int $companyId = null,
        public readonly ?string $notes = null,
    ) {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
