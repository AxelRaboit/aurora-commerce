<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Crm module's own setting parameters. Persisted keys kept identical to the
 * legacy core enum values so no DB migration is needed.
 */
enum CrmSettingEnum: string implements ApplicationParameterEnumInterface
{
    case SyncOrders = 'crm_sync_orders';
    case DealPrefix = 'backend_crm_deal_prefix';
    case ContactPrefix = 'backend_crm_contact_prefix';
    case CompanyPrefix = 'backend_crm_company_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SyncOrders => 'backend.parameters.crm_sync_orders.label',
            self::DealPrefix => 'backend.parameters.crm_deal_prefix.label',
            self::ContactPrefix => 'backend.parameters.crm_contact_prefix.label',
            self::CompanyPrefix => 'backend.parameters.crm_company_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SyncOrders => 'backend.parameters.crm_sync_orders.description',
            self::DealPrefix => 'backend.parameters.crm_deal_prefix.description',
            self::ContactPrefix => 'backend.parameters.crm_contact_prefix.description',
            self::CompanyPrefix => 'backend.parameters.crm_company_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::SyncOrders => '0',
            self::DealPrefix => SequencePrefixEnum::Deal->value,
            self::ContactPrefix => SequencePrefixEnum::Contact->value,
            self::CompanyPrefix => SequencePrefixEnum::Company->value,
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::SyncOrders => 'bool',
            self::DealPrefix, self::ContactPrefix, self::CompanyPrefix => 'string',
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::SyncOrders => 'crm',
            self::DealPrefix, self::ContactPrefix, self::CompanyPrefix => 'sequences',
        };
    }
}
