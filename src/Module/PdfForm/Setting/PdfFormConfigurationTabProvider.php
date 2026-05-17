<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Setting;

use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Configuration\Setting\Configuration\SettingFieldDescriptor;

final readonly class PdfFormConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    public function getTabs(): array
    {
        $fields = [];
        foreach (PdfFormSettingEnum::cases() as $case) {
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
