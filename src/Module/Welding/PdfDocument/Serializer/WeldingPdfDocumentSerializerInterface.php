<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Serializer;

use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;

interface WeldingPdfDocumentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingPdfDocumentInterface $document): array;
}
