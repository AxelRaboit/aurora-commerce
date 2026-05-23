<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes welding settings to the shared `sequences` tab (priority 90).
 * Other modules (Billing, GED, …) contribute to the same tab; the registry
 * merges them by id.
 */
final readonly class WeldingConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        'sequences' => 90,
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach (WeldingSettingEnum::cases() as $case) {
            $fieldsByGroup[$case->getGroup()][] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        $tabs = [];
        foreach ($fieldsByGroup as $group => $fields) {
            $tabs[] = new ConfigurationTab(
                id: $group,
                priority: self::TAB_PRIORITY[$group] ?? 200,
                fields: $fields,
            );
        }

        return $tabs;
    }
}
