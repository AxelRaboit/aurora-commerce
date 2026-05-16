<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Configuration;

/**
 * Single source of truth for which settings exist, which tab they belong to,
 * and how they should be validated/rendered. Aggregates every
 * {@see ConfigurationTabProviderInterface} into one indexed view, used by:
 *
 *  - {@see \Aurora\Core\Setting\View\SettingsViewBuilder} to build the
 *    grouped Twig payload.
 *  - {@see \Aurora\Core\Setting\Controller\Backend\SettingsController} to
 *    decide whether an incoming `key` is admin-writable.
 *
 * The registry replaces direct `ApplicationParameterEnum::tryFrom(...)` calls
 * downstream, which is what makes module-contributed settings possible.
 */
final class SettingDefinitionRegistry
{
    /**
     * @var list<ConfigurationTab>|null Resolved on first access.
     */
    private ?array $tabs = null;

    /**
     * @var array<string, SettingFieldDescriptor>|null Key → descriptor lookup, built lazily.
     */
    private ?array $fieldsByKey = null;

    /**
     * @param iterable<ConfigurationTabProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    /**
     * Returns all contributed tabs sorted by priority (ascending), tabs with
     * the same priority preserve provider iteration order. Tabs that share
     * the same `$id` across providers are NOT merged — duplicates would
     * indicate a contribution mistake and are surfaced as-is so the bug is
     * visible in the UI rather than silently swallowed.
     *
     * @return list<ConfigurationTab>
     */
    public function getTabs(): array
    {
        if (null !== $this->tabs) {
            return $this->tabs;
        }

        $collected = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getTabs() as $tab) {
                $collected[] = $tab;
            }
        }

        usort($collected, static fn (ConfigurationTab $a, ConfigurationTab $b): int => $a->priority <=> $b->priority);

        return $this->tabs = array_values($collected);
    }

    public function getField(string $key): ?SettingFieldDescriptor
    {
        return $this->fieldsByKey()[$key] ?? null;
    }

    /**
     * Convenience predicate replacing
     * `ApplicationParameterEnum::tryFrom($key)?->isAdminAccessible()`. Any key
     * surfaced by a registered tab is admin-accessible by construction.
     */
    public function isAdminAccessible(string $key): bool
    {
        return isset($this->fieldsByKey()[$key]);
    }

    /**
     * @return array<string, SettingFieldDescriptor>
     */
    private function fieldsByKey(): array
    {
        if (null !== $this->fieldsByKey) {
            return $this->fieldsByKey;
        }

        $map = [];
        foreach ($this->getTabs() as $tab) {
            foreach ($tab->fields as $field) {
                $map[$field->key] = $field;
            }
        }

        return $this->fieldsByKey = $map;
    }
}
