<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

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

    /**
     * Only the `crm` tab is gated on the CRM module toggle. The
     * `sequences` tab is shared with other modules and is left
     * unrestricted so disabling CRM doesn't pull the rug from other
     * modules' prefix settings.
     */
    private const array TAB_MODULE_TOGGLE = [
        'crm' => ModuleParameterEnum::CrmBackend,
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
                placeholderKey: $case->getPlaceholder(),
            );
        }

        $tabs = [];
        foreach ($fieldsByGroup as $group => $fields) {
            $tabs[] = new ConfigurationTab(
                id: $group,
                priority: self::TAB_PRIORITY[$group] ?? 200,
                fields: $fields,
                moduleToggle: self::TAB_MODULE_TOGGLE[$group] ?? null,
            );
        }

        return $tabs;
    }
}
