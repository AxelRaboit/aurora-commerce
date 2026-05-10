<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Enum;

enum PdfFieldTypeEnum: string
{
    case Text = 'text';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Dropdown = 'dropdown';
    case Date = 'date';
    case Signature = 'signature';

    public function getLabelKey(): string
    {
        return 'backend.pdfform.fields.type_'.$this->value;
    }
}
