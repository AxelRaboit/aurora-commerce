<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingPdfDocumentStatusEnum: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.welding.pdf_documents.status_'.$this->value;
    }
}
