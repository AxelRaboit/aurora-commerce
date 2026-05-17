<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Configuration\Setting\Configuration\SettingFieldDescriptor;

/**
 * Contributes to two tabs of the admin Settings page:
 *  - `ecommerce` (priority 110) — module-specific switches (stock, …).
 *  - `sequences` (priority 90, shared) — reference prefixes, merged with
 *    other modules' contributions via the registry's merge-by-id.
 */
final readonly class EcommerceConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        'ecommerce' => 110,
        'sequences' => 90,
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach (EcommerceSettingEnum::cases() as $case) {
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
