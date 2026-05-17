<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Core\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;

/**
 * Surfaces the Assistant module's settings as a dedicated tab on the
 * `/backend/settings` page. Adding another `case` to
 * {@see AssistantSettingEnum} immediately shows up in the same tab.
 *
 * Priority 120 keeps it grouped with the other module-contributed tabs
 * (notes = 110, …) right after the Core platform-wide tabs (10–100).
 */
final readonly class AssistantConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (AssistantSettingEnum::cases() as $case) {
            $fields[] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        return [
            new ConfigurationTab(id: 'assistant', priority: 120, fields: $fields),
        ];
    }
}
