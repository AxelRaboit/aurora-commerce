<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Ecommerce module's own setting parameters. Persisted keys kept identical
 * to the legacy core enum values so no DB migration is needed.
 */
enum EcommerceSettingEnum: string implements ApplicationParameterEnumInterface
{
    case LowStockThreshold = 'backend_ecommerce_low_stock_threshold';
    case OrderPrefix = 'backend_ecommerce_order_prefix';
    case ListingPrefix = 'backend_ecommerce_listing_prefix';
    case CartPrefix = 'backend_ecommerce_cart_prefix';
    case CartItemPrefix = 'backend_ecommerce_cart_item_prefix';
    case OrderLinePrefix = 'backend_ecommerce_order_line_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.label',
            self::OrderPrefix => 'backend.parameters.ecommerce_order_prefix.label',
            self::ListingPrefix => 'backend.parameters.ecommerce_listing_prefix.label',
            self::CartPrefix => 'backend.parameters.ecommerce_cart_prefix.label',
            self::CartItemPrefix => 'backend.parameters.ecommerce_cart_item_prefix.label',
            self::OrderLinePrefix => 'backend.parameters.ecommerce_order_line_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'backend.parameters.ecommerce_low_stock_threshold.description',
            self::OrderPrefix => 'backend.parameters.ecommerce_order_prefix.description',
            self::ListingPrefix => 'backend.parameters.ecommerce_listing_prefix.description',
            self::CartPrefix => 'backend.parameters.ecommerce_cart_prefix.description',
            self::CartItemPrefix => 'backend.parameters.ecommerce_cart_item_prefix.description',
            self::OrderLinePrefix => 'backend.parameters.ecommerce_order_line_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::LowStockThreshold => '5',
            self::OrderPrefix => SequencePrefixEnum::Order->value,
            self::ListingPrefix => SequencePrefixEnum::Listing->value,
            self::CartPrefix => SequencePrefixEnum::Cart->value,
            self::CartItemPrefix => SequencePrefixEnum::CartItem->value,
            self::OrderLinePrefix => SequencePrefixEnum::OrderLine->value,
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'int',
            self::OrderPrefix, self::ListingPrefix, self::CartPrefix, self::CartItemPrefix, self::OrderLinePrefix => 'string',
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::LowStockThreshold => 'ecommerce',
            self::OrderPrefix, self::ListingPrefix, self::CartPrefix, self::CartItemPrefix, self::OrderLinePrefix => 'sequences',
        };
    }

    /**
     * No placeholder by default — override on a per-case basis when an
     * example value is genuinely clearer than the description alone.
     */
    public function getPlaceholder(): ?string
    {
        return null;
    }
}
