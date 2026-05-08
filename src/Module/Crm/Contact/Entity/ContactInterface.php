<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;

interface ContactInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getFullName(): string;

    public function getFirstName(): string;

    public function setFirstName(string $firstName): static;

    public function getLastName(): string;

    public function setLastName(string $lastName): static;

    public function getEmail(): ?string;

    public function setEmail(?string $email): static;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): static;

    public function getCompany(): ?CompanyInterface;

    public function setCompany(?CompanyInterface $company): static;

    public function getDisplayCompany(): ?string;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;
}
