<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Doctrine\Common\Collections\Collection;

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

    public function getSource(): ?ContactSourceEnum;

    public function setSource(?ContactSourceEnum $source): static;

    /** @return Collection<int, ContactTagInterface> */
    public function getContactTags(): Collection;

    public function addContactTag(ContactTagInterface $contactTag): static;

    public function removeContactTag(ContactTagInterface $contactTag): static;

    public function clearContactTags(): static;
}
