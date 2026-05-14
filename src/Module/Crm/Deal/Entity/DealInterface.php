<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use DateTimeImmutable;

interface DealInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getStage(): DealStageEnum;

    public function setStage(DealStageEnum $stage): static;

    public function getValue(): ?string;

    public function setValue(?string $value): static;

    public function getContact(): ?ContactInterface;

    public function setContact(?ContactInterface $contact): static;

    public function getCompany(): ?CompanyInterface;

    public function setCompany(?CompanyInterface $company): static;

    public function getClosingDate(): ?DateTimeImmutable;

    public function setClosingDate(?DateTimeImmutable $closingDate): static;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;
}
