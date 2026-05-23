<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Manager;

use Aurora\Module\Welding\PdfTemplate\Dto\WeldingPdfTemplateInputInterface;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;

interface WeldingPdfTemplateManagerInterface
{
    public function create(WeldingPdfTemplateInputInterface $input): WeldingPdfTemplateInterface;

    public function update(WeldingPdfTemplateInterface $template, WeldingPdfTemplateInputInterface $input): void;

    public function delete(WeldingPdfTemplateInterface $template): void;

    /**
     * Detects AcroForm fields from the template PDF and synchronises WeldingPdfTemplateField entities.
     *
     * @return list<array{name: string, type: string}>
     */
    public function detectAndSyncFields(WeldingPdfTemplateInterface $template): array;
}
