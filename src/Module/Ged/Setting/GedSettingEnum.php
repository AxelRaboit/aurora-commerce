<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Setting;

use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

enum GedSettingEnum: string implements ApplicationParameterEnumInterface
{
    case DocumentPrefix = 'backend_ged_document_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DocumentPrefix => 'backend.parameters.ged_document_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DocumentPrefix => 'backend.parameters.ged_document_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::DocumentPrefix => 'DOC',
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
