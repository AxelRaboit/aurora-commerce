<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

/**
 * Contributes the block-notes settings to the same "notes" tab on
 * `/backend/settings`. The registry merges tabs with the same id from
 * every provider, so Markdown's and Block's fields appear side by side
 * under one header — no UI duplication.
 *
 * Priority 111 places these right after Markdown's (110) within the tab.
 */
final readonly class NotesBlockConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (BlockNoteSettingEnum::cases() as $case) {
            $fields[] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        return [
            new ConfigurationTab(
                id: 'notes',
                priority: 111,
                fields: $fields,
                moduleToggle: ModuleParameterEnum::NotesBackend,
            ),
        ];
    }
}
