<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Dto;

interface PdfTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PdfTemplateInputInterface;
}
