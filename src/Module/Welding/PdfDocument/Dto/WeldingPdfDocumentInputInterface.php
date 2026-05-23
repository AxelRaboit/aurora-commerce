<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Dto;

interface WeldingPdfDocumentInputInterface
{
    public function getTemplateId(): int;

    public function getLabel(): ?string;

    /** @return array<string, string> */
    public function getFieldValues(): array;

    public function getContextType(): ?string;

    public function getContextId(): ?int;
}
