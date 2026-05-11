<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggle;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\ModuleToggleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleToggleRegistryTest extends TestCase
{
    public function testAggregatesTogglesFromProviderImplementingModules(): void
    {
        $registry = new ModuleToggleRegistry([
            new StubProvider('module_a', [
                new ModuleToggle('a.main', 'label.a', 'desc.a', moduleId: 'a'),
                new ModuleToggle('a.sub', 'label.a.sub', 'desc.a.sub', parentKey: 'a.main'),
            ]),
            new StubProvider('module_b', [
                new ModuleToggle('b.main', 'label.b', 'desc.b', moduleId: 'b'),
            ]),
        ]);

        self::assertCount(3, $registry->getAll());
        self::assertSame('a.main', $registry->get('a.main')?->key);
        self::assertTrue($registry->has('a.sub'));
        self::assertFalse($registry->has('unknown'));
    }

    public function testSkipsModulesNotImplementingProviderInterface(): void
    {
        $registry = new ModuleToggleRegistry([
            new StubNonProviderModule('plain'),
            new StubProvider('with_toggles', [
                new ModuleToggle('x', 'label.x', 'desc.x', moduleId: 'x'),
            ]),
        ]);

        self::assertCount(1, $registry->getAll());
        self::assertTrue($registry->has('x'));
    }

    public function testGetTopLevelFiltersToToggleWithModuleId(): void
    {
        $registry = new ModuleToggleRegistry([
            new StubProvider('m', [
                new ModuleToggle('top', 'l', 'd', moduleId: 'm'),
                new ModuleToggle('child', 'l', 'd', parentKey: 'top'),
                new ModuleToggle('grandchild', 'l', 'd', parentKey: 'child'),
            ]),
        ]);

        $topLevel = $registry->getTopLevel();
        self::assertCount(1, $topLevel);
        self::assertSame('top', $topLevel[0]->key);
    }

    public function testLaterModuleDeclarationsOverrideEarlierOnesForSameKey(): void
    {
        $registry = new ModuleToggleRegistry([
            new StubProvider('first', [
                new ModuleToggle('shared', 'first.label', 'first.desc', moduleId: 'first'),
            ]),
            new StubProvider('second', [
                new ModuleToggle('shared', 'second.label', 'second.desc', moduleId: 'second'),
            ]),
        ]);

        self::assertSame('second.label', $registry->get('shared')?->labelKey);
    }
}

final class StubProvider implements ModuleInterface, ModuleToggleProviderInterface
{
    /** @param list<ModuleToggle> $toggles */
    public function __construct(private readonly string $id, private readonly array $toggles) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getNavSections(): array
    {
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }

    public function getToggles(): array
    {
        return $this->toggles;
    }
}

final class StubNonProviderModule implements ModuleInterface
{
    public function __construct(private readonly string $id) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getNavSections(): array
    {
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }
}
