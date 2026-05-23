<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Dto;

interface WeldingPdfDocumentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfDocumentInputInterface;
}
