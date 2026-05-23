<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Serializer;

use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;

interface WeldingPdfTemplateFieldSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingPdfTemplateFieldInterface $field): array;
}
