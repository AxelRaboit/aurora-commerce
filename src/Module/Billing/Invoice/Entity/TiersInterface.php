<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;

interface TiersInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getType(): TiersTypeEnum;

    public function setType(TiersTypeEnum $type): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getVatNumber(): ?string;

    public function setVatNumber(?string $vatNumber): self;

    public function getRegistrationNumber(): ?string;

    public function setRegistrationNumber(?string $registrationNumber): self;

    public function getIban(): ?string;

    public function setIban(?string $iban): self;

    public function getBic(): ?string;

    public function setBic(?string $bic): self;

    public function getEmail(): ?string;

    public function setEmail(?string $email): self;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): self;

    public function getAddress(): ?string;

    public function setAddress(?string $address): self;

    public function getCountryCode(): ?string;

    public function setCountryCode(?string $countryCode): self;

    public function getWebsite(): ?string;

    public function setWebsite(?string $website): self;

    public function getLegalForm(): ?string;

    public function setLegalForm(?string $legalForm): self;

    public function getBankName(): ?string;

    public function setBankName(?string $bankName): self;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): self;

    public function getCompany(): ?CompanyInterface;

    public function setCompany(?CompanyInterface $company): self;

    public function getReference(): ?string;

    public function setReference(?string $reference): self;
}
