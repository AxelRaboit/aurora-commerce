<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;

interface PdfDocumentInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getTemplate(): ?PdfTemplateInterface;

    public function setTemplate(?PdfTemplateInterface $template): static;

    public function getStatus(): PdfDocumentStatusEnum;

    public function setStatus(PdfDocumentStatusEnum $status): static;

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

    /** Relative path within the pdfform storage directory (e.g. 2026-05/PDF-000001.pdf). */
    public function getFilePath(): ?string;

    public function setFilePath(?string $filePath): static;
}
