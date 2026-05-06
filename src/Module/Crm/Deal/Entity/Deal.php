<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'backend_crm_deals')]
#[ORM\HasLifecycleCallbacks]
class Deal
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_deal_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 200)]
    private string $name;

    #[ORM\Column(length: 20, enumType: DealStageEnum::class, options: ['default' => 'lead'])]
    private DealStageEnum $stage = DealStageEnum::Lead;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $closingDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

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
