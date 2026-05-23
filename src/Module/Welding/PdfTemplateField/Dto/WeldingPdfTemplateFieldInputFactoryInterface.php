<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Dto;

interface WeldingPdfTemplateFieldInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfTemplateFieldInputInterface;
}
