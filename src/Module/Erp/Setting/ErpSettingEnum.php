<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum ErpSettingEnum: string implements ApplicationParameterEnumInterface
{
    case ProductPrefix = 'backend_erp_product_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ProductPrefix => 'backend.parameters.erp_product_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ProductPrefix => 'backend.parameters.erp_product_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::ProductPrefix => SequencePrefixEnum::Product->value,
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

    /**
     * No placeholder by default — override on a per-case basis when an
     * example value is genuinely clearer than the description alone.
     */
    public function getPlaceholder(): ?string
    {
        return null;
    }
}
