<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Manager;

use Aurora\Module\Welding\PdfTemplateField\Dto\WeldingPdfTemplateFieldInputInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;

interface WeldingPdfTemplateFieldManagerInterface
{
    public function update(WeldingPdfTemplateFieldInterface $field, WeldingPdfTemplateFieldInputInterface $input): void;

    public function delete(WeldingPdfTemplateFieldInterface $field): void;
}
