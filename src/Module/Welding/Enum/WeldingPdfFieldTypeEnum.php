<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingPdfFieldTypeEnum: string
{
    case Text = 'text';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Dropdown = 'dropdown';
    case Date = 'date';
    case Signature = 'signature';

    public function getLabelKey(): string
    {
        return 'backend.welding.pdf_template_fields.type_'.$this->value;
    }
}
