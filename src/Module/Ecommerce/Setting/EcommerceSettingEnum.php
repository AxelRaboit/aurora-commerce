<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Ecommerce module's own setting parameters. Implements the same contract
 * as {@see \Aurora\Core\Setting\Enum\ApplicationParameterEnum} so the
 * setting repository's `getOrDefault()` accepts these cases transparently
 * — but the cases live with the module that owns them, which is what makes
 * the Settings page extensible.
 *
 * Persisted key kept identical to the legacy core enum value (no DB migration
 * needed when the case moved here).
 */
enum EcommerceSettingEnum: string implements ApplicationParameterEnumInterface
{
    case LowStockThreshold = 'backend_ecommerce_low_stock_threshold';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::LowStockThreshold => '5',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'int',
        };
    }

    public function getGroup(): string
    {
        return 'ecommerce';
    }
}
