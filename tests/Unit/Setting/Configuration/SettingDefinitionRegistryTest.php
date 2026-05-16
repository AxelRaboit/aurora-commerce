<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Configuration;

use Aurora\Core\Setting\Configuration\ConfigurationTab;
use Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Core\Setting\Configuration\SettingDefinitionRegistry;
use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;
use PHPUnit\Framework\TestCase;

final class SettingDefinitionRegistryTest extends TestCase
{
    public function test_aggregates_tabs_from_all_providers_and_sorts_by_priority(): void
    {
        $registry = new SettingDefinitionRegistry([
            $this->provider([new ConfigurationTab(id: 'late', priority: 90, fields: [])]),
            $this->provider([
                new ConfigurationTab(id: 'early', priority: 10, fields: []),
                new ConfigurationTab(id: 'middle', priority: 50, fields: []),
            ]),
        ]);

        $tabs = $registry->getTabs();

        self::assertSame(['early', 'middle', 'late'], array_map(static fn ($tab) => $tab->id, $tabs));
    }

    public function test_indexes_fields_by_key_for_O1_lookup(): void
    {
        $field = new SettingFieldDescriptor(
            key: 'custom_module.toggle',
            type: 'bool',
            labelKey: 'custom.label',
            descriptionKey: 'custom.help',
            defaultValue: '0',
        );

        $registry = new SettingDefinitionRegistry([
            $this->provider([new ConfigurationTab(id: 'custom', priority: 100, fields: [$field])]),
        ]);

        self::assertSame($field, $registry->getField('custom_module.toggle'));
        self::assertTrue($registry->isAdminAccessible('custom_module.toggle'));
        self::assertFalse($registry->isAdminAccessible('unknown_key'));
    }

    public function test_caches_resolved_tabs_so_providers_run_once_per_request(): void
    {
        $provider = new class implements ConfigurationTabProviderInterface {
            public int $calls = 0;

            public function getTabs(): array
            {
                ++$this->calls;

                return [new ConfigurationTab(id: 'cached', priority: 1, fields: [])];
            }
        };

        $registry = new SettingDefinitionRegistry([$provider]);

        $registry->getTabs();
        $registry->getTabs();
        $registry->getField('any');

        self::assertSame(1, $provider->calls);
    }

    /**
     * @param list<ConfigurationTab> $tabs
     */
    private function provider(array $tabs): ConfigurationTabProviderInterface
    {
        return new class($tabs) implements ConfigurationTabProviderInterface {
            /** @param list<ConfigurationTab> $tabs */
            public function __construct(private readonly array $tabs) {}

            public function getTabs(): array
            {
                return $this->tabs;
            }
        };
    }
}
