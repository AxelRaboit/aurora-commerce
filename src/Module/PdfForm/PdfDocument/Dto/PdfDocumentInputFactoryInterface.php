<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Dto;

interface PdfDocumentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PdfDocumentInputInterface;
}
