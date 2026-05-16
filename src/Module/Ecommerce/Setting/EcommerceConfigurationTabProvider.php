<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Core\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes the "Ecommerce" tab to the admin Settings page. Each case of
 * {@see EcommerceSettingEnum} becomes one {@see SettingFieldDescriptor},
 * rendered by the generic Vue field renderer (no custom UI needed).
 *
 * Priority places the tab below the Core groups (general/reading/…) and
 * before more specialized module tabs.
 */
final readonly class EcommerceConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (EcommerceSettingEnum::cases() as $case) {
            $fields[] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        return [
            new ConfigurationTab(id: 'ecommerce', priority: 110, fields: $fields),
        ];
    }
}
