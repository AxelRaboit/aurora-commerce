<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Manager;

use Aurora\Module\PdfForm\PdfTemplateField\Dto\PdfTemplateFieldInputInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;

interface PdfTemplateFieldManagerInterface
{
    public function update(PdfTemplateFieldInterface $field, PdfTemplateFieldInputInterface $input): void;

    public function delete(PdfTemplateFieldInterface $field): void;
}
