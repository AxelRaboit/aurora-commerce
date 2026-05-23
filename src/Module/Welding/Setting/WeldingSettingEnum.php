<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Setting;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum WeldingSettingEnum: string implements ApplicationParameterEnumInterface
{
    case ReferencePrefix = 'backend_welding_reference_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'WLD',
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
