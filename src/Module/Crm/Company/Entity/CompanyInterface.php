<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Entity;

use Aurora\Core\Contract\TimestampableInterface;

interface CompanyInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getIndustry(): ?string;

    public function setIndustry(?string $industry): static;

    public function getWebsite(): ?string;

    public function setWebsite(?string $website): static;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): static;

    public function getAddress(): ?string;

    public function setAddress(?string $address): static;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;
}
