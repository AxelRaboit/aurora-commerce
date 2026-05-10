<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractContact implements ContactInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 100)]
    protected string $firstName;

    #[ORM\Column(length: 100)]
    protected string $lastName;

    #[ORM\Column(length: 180, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: CompanyInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CompanyInterface $company = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    public function getFullName(): string
    {
        return mb_trim($this->firstName.' '.$this->lastName);
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompany(): ?CompanyInterface
    {
        return $this->company;
    }

    public function setCompany(?CompanyInterface $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getDisplayCompany(): ?string
    {
        return $this->company?->getName();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }
}
