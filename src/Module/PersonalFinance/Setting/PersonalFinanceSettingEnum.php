<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Setting;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum PersonalFinanceSettingEnum: string implements ApplicationParameterEnumInterface
{
    case DefaultCurrency = 'personal_finance_default_currency';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DefaultCurrency => 'backend.parameters.personal_finance_default_currency.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DefaultCurrency => 'backend.parameters.personal_finance_default_currency.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::DefaultCurrency => 'EUR',
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return 'personal_finance';
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
