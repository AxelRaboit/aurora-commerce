<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Configuration\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes to two tabs of the admin Settings page:
 *  - `crm` (priority 120) — module-specific switches.
 *  - `sequences` (priority 90, shared) — reference prefixes, merged with
 *    other modules' prefix contributions via the registry's merge-by-id.
 */
final readonly class CrmConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        'crm' => 120,
        'sequences' => 90,
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach (CrmSettingEnum::cases() as $case) {
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
