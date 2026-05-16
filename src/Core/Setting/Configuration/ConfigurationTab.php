<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Configuration;

/**
 * One tab in the admin Settings page. The `$id` is both the persisted
 * "group" identifier (matching the legacy `ApplicationParameterEnum::getGroup()`
 * values) and the translation-key suffix the Vue layer uses to resolve the
 * tab label / description (`backend.settings.tabs.{id}` + `_description`).
 *
 * @phpstan-import-type SelectOption from SettingFieldDescriptor
 */
class ConfigurationTab
{
    /**
     * @param list<SettingFieldDescriptor> $fields
     */
    public function __construct(
        public readonly string $id,
        public readonly int $priority,
        public readonly array $fields,
        /**
         * Forces the tab to render even when `$fields` is empty — for tabs
         * whose body is a custom Vue component (e.g. navigation aliases,
         * appearance palette) that draws its own content.
         */
        public readonly bool $alwaysVisible = false,
    ) {}
}
