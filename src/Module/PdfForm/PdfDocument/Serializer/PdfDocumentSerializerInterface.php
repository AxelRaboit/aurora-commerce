<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Serializer;

use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;

interface PdfDocumentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PdfDocumentInterface $document): array;
}
