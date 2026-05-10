<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Enum;

enum PdfDocumentStatusEnum: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.pdfform.documents.status_'.$this->value;
    }
}
