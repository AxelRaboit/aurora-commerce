<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

enum PdfFormSettingEnum: string implements ApplicationParameterEnumInterface
{
    case DocumentPrefix = 'backend_pdfform_document_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DocumentPrefix => 'backend.parameters.pdfform_document_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DocumentPrefix => 'backend.parameters.pdfform_document_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::DocumentPrefix => SequencePrefixEnum::PdfFormDocument->value,
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return 'sequences';
    }
}
