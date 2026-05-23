<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Manager;

use Aurora\Module\Welding\PdfDocument\Dto\WeldingPdfDocumentInputInterface;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;

interface WeldingPdfDocumentManagerInterface
{
    public function generate(WeldingPdfDocumentInputInterface $input): WeldingPdfDocumentInterface;

    public function delete(WeldingPdfDocumentInterface $document): void;

    public function getAbsolutePath(WeldingPdfDocumentInterface $document): ?string;
}
