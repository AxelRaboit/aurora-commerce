<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;

final readonly class PersonalFinanceConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        'personal_finance' => 130,
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach (PersonalFinanceSettingEnum::cases() as $case) {
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
                moduleToggle: PersonalFinanceModuleParameterEnum::Backend->value,
            );
        }

        return $tabs;
    }
}
