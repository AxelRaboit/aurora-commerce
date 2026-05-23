<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Serializer;

use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;

interface WeldingPdfTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingPdfTemplateInterface $template): array;
}
