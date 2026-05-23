<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Setting;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum WeldingSettingEnum: string implements ApplicationParameterEnumInterface
{
    case ReferencePrefix = 'backend_welding_reference_prefix';
    case NotificationEmail = 'backend_welding_notification_email';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.label',
            self::NotificationEmail => 'backend.parameters.welding_notification_email.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.description',
            self::NotificationEmail => 'backend.parameters.welding_notification_email.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'WLD',
            self::NotificationEmail => '',
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'sequences',
            self::NotificationEmail => 'welding',
        };
    }
}
