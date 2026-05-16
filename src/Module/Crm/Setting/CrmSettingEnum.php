<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Crm module's own setting parameters. Persisted key kept identical to the
 * legacy core enum value so no DB migration is needed.
 */
enum CrmSettingEnum: string implements ApplicationParameterEnumInterface
{
    case SyncOrders = 'crm_sync_orders';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SyncOrders => 'backend.parameters.crm_sync_orders.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SyncOrders => 'backend.parameters.crm_sync_orders.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::SyncOrders => '0',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::SyncOrders => 'bool',
        };
    }

    public function getGroup(): string
    {
        return 'crm';
    }
}
