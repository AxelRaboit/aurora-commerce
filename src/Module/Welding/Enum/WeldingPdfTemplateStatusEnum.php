<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingPdfTemplateStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.welding.pdf_templates.status_'.$this->value;
    }
}
