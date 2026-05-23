<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Doctrine\Common\Collections\Collection;

interface WeldingPdfTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getStatus(): WeldingPdfTemplateStatusEnum;

    public function setStatus(WeldingPdfTemplateStatusEnum $status): static;

    public function getFile(): ?MediaInterface;

    public function setFile(?MediaInterface $file): static;

    public function isFlattenOnGenerate(): bool;

    public function setFlattenOnGenerate(bool $flatten): static;

    public function isRequiresSignature(): bool;

    public function setRequiresSignature(bool $requiresSignature): static;

    /** @return Collection<int, WeldingPdfTemplateFieldInterface> */
    public function getFields(): Collection;
}
