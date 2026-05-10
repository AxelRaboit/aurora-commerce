<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Doctrine\Common\Collections\Collection;

interface PdfTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getStatus(): PdfTemplateStatusEnum;

    public function setStatus(PdfTemplateStatusEnum $status): static;

    public function getFile(): ?MediaInterface;

    public function setFile(?MediaInterface $file): static;

    public function isFlattenOnGenerate(): bool;

    public function setFlattenOnGenerate(bool $flatten): static;

    public function isRequiresSignature(): bool;

    public function setRequiresSignature(bool $requiresSignature): static;

    /** @return Collection<int, PdfTemplateFieldInterface> */
    public function getFields(): Collection;
}
