<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Serializer;

use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;

interface PdfTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PdfTemplateInterface $template): array;
}
