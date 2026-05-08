<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractDeal implements DealInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 200)]
    protected string $name;

    #[ORM\Column(length: 20, enumType: DealStageEnum::class, options: ['default' => 'lead'])]
    protected DealStageEnum $stage = DealStageEnum::Lead;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    protected ?string $value = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Company $company = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $closingDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStage(): DealStageEnum
    {
        return $this->stage;
    }

    public function setStage(DealStageEnum $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getClosingDate(): ?DateTimeImmutable
    {
        return $this->closingDate;
    }

    public function setClosingDate(?DateTimeImmutable $closingDate): static
    {
        $this->closingDate = $closingDate;

        return $this;
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
