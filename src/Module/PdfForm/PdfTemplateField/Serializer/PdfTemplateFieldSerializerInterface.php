<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Serializer;

use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;

interface PdfTemplateFieldSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PdfTemplateFieldInterface $field): array;
}
