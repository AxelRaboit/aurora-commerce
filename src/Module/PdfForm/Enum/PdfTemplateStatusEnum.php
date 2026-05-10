<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Enum;

enum PdfTemplateStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.pdfform.templates.status_'.$this->value;
    }
}
