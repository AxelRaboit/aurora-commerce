<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;

interface WeldingPdfDocumentInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getTemplate(): ?WeldingPdfTemplateInterface;

    public function setTemplate(?WeldingPdfTemplateInterface $template): static;

    public function getStatus(): WeldingPdfDocumentStatusEnum;

    public function setStatus(WeldingPdfDocumentStatusEnum $status): static;

    public function getLabel(): ?string;

    public function setLabel(?string $label): static;

    /** @return array<string, string> */
    public function getFieldValues(): array;

    /** @param array<string, string> $fieldValues */
    public function setFieldValues(array $fieldValues): static;

    public function getContextType(): ?string;

    public function setContextType(?string $contextType): static;

    public function getContextId(): ?int;

    public function setContextId(?int $contextId): static;

    /** Relative path within the welding PDF storage directory (e.g. 2026/05/PDF-000001.pdf). */
    public function getFilePath(): ?string;

    public function setFilePath(?string $filePath): static;
}
