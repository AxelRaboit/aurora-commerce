<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
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

    public function getContact(): ?Contact;

    public function setContact(?Contact $contact): static;

    public function getCompany(): ?Company;

    public function setCompany(?Company $company): static;

    public function getClosingDate(): ?DateTimeImmutable;

    public function setClosingDate(?DateTimeImmutable $closingDate): static;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;
}
