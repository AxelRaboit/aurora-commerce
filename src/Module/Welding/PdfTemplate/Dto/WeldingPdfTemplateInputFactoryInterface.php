<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Dto;

interface WeldingPdfTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfTemplateInputInterface;
}
