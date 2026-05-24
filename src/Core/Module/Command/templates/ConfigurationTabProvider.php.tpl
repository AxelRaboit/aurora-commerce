<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes the "{{MODULE_LABEL}}" tab to the admin Settings page. Edit
 * {@see {{MODULE}}SettingEnum} to add/change settings — this provider
 * automatically builds the tab from the enum cases.
 */
final readonly class {{MODULE}}ConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        '{{MODULE_ID}}' => 100,
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach ({{MODULE}}SettingEnum::cases() as $case) {
            $fieldsByGroup[$case->getGroup()][] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
                placeholderKey: $case->getPlaceholder(),
            );
        }

        $tabs = [];
        foreach ($fieldsByGroup as $group => $fields) {
            $tabs[] = new ConfigurationTab(
                id: $group,
                priority: self::TAB_PRIORITY[$group] ?? 100,
                fields: $fields,
            );
        }

        return $tabs;
    }
}
