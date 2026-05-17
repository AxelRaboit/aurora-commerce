<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Core\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;

/**
 * Surfaces the Assistant module's settings as a dedicated tab on the
 * `/backend/settings` page.
 */
final readonly class AssistantConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array PROVIDER_OPTIONS = [
        ['value' => 'ollama', 'label' => 'Ollama (local)'],
        ['value' => 'anthropic', 'label' => 'Anthropic Claude'],
    ];

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
                options: AssistantSettingEnum::Provider === $case ? self::PROVIDER_OPTIONS : null,
            );
        }

        return [
            new ConfigurationTab(id: 'assistant', priority: 120, fields: $fields, componentName: 'assistant-settings'),
        ];
    }
}
