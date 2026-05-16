<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Core\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes the "Crm" tab to the admin Settings page. One descriptor per
 * {@see CrmSettingEnum} case, rendered by the generic Vue field renderer.
 */
final readonly class CrmConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (CrmSettingEnum::cases() as $case) {
            $fields[] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        return [
            new ConfigurationTab(id: 'crm', priority: 120, fields: $fields),
        ];
    }
}
