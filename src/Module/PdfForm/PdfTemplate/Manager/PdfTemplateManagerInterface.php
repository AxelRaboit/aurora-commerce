<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Manager;

use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInputInterface;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;

interface PdfTemplateManagerInterface
{
    public function create(PdfTemplateInputInterface $input): PdfTemplateInterface;

    public function update(PdfTemplateInterface $template, PdfTemplateInputInterface $input): void;

    public function delete(PdfTemplateInterface $template): void;

    /**
     * Detects AcroForm fields from the template PDF and synchronises PdfTemplateField entities.
     *
     * @return list<array{name: string, type: string}>
     */
    public function detectAndSyncFields(PdfTemplateInterface $template): array;
}
