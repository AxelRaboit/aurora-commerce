<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractTiers implements TiersInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 50, enumType: TiersTypeEnum::class)]
    protected TiersTypeEnum $type;

    #[ORM\Column(length: 200)]
    protected string $name;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $vatNumber = null;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $registrationNumber = null;

    #[ORM\Column(length: 34, nullable: true)]
    protected ?string $iban = null;

    #[ORM\Column(length: 11, nullable: true)]
    protected ?string $bic = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $address = null;

    #[ORM\Column(length: 2, nullable: true)]
    protected ?string $countryCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $website = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $legalForm = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $bankName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    /** Optional link to a CRM Company — kept loose so Billing stays independent. */
    #[ORM\ManyToOne(targetEntity: CompanyInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CompanyInterface $company = null;

    public function getType(): TiersTypeEnum
    {
        return $this->type;
    }

    public function setType(TiersTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): self
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): self
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCompany(): ?CompanyInterface
    {
        return $this->company;
    }

    public function setCompany(?CompanyInterface $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }
}
