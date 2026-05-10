<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Manager;

use Aurora\Module\PdfForm\PdfDocument\Dto\PdfDocumentInputInterface;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;

interface PdfDocumentManagerInterface
{
    public function generate(PdfDocumentInputInterface $input): PdfDocumentInterface;

    public function delete(PdfDocumentInterface $document): void;

    public function getAbsolutePath(PdfDocumentInterface $document): ?string;
}
