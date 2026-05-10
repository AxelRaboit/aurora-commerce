<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Dto;

interface PdfTemplateFieldInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PdfTemplateFieldInputInterface;
}
