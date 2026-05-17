<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Setting;

use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Configuration\Setting\Configuration\SettingFieldDescriptor;

/**
 * Erp module's contribution to the admin Settings page. Adds its prefix
 * descriptor to the shared `sequences` tab via the registry's merge-by-id.
 */
final readonly class ErpConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (ErpSettingEnum::cases() as $case) {
            $fields[] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        return [
            new ConfigurationTab(id: 'sequences', priority: 90, fields: $fields),
        ];
    }
}
