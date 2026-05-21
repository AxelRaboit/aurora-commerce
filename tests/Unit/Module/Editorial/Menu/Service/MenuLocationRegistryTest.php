<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Menu\Service;

use Aurora\Module\Editorial\Menu\Contract\MenuLocationProviderInterface;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class MenuLocationRegistryTest extends TestCase
{
    private function makeProvider(array $locations): MenuLocationProviderInterface
    {
        $provider = $this->createMock(MenuLocationProviderInterface::class);
        $provider->method('getMenuLocations')->willReturn($locations);

        return $provider;
    }

    public function testEmptyProviderListYieldsEmptyRegistry(): void
    {
        $registry = new MenuLocationRegistry([]);

        self::assertSame([], $registry->all());
    }

    public function testRegistryAggregatesFromMultipleProviders(): void
    {
        $providerA = $this->makeProvider([
            'primary' => ['name' => 'Primary', 'description' => null, 'defaultItems' => []],
        ]);
        $providerB = $this->makeProvider([
            'footer' => ['name' => 'Footer', 'description' => 'Bottom nav', 'defaultItems' => []],
        ]);

        $registry = new MenuLocationRegistry([$providerA, $providerB]);

        $all = $registry->all();
        self::assertCount(2, $all);
        self::assertArrayHasKey('primary', $all);
        self::assertArrayHasKey('footer', $all);
        self::assertSame('Bottom nav', $all['footer']['description']);
    }

    public function testLaterProviderOverridesEarlierForSameLocationKey(): void
    {
        // First wins is not the rule — order in the iterable determines
        // priority, last writer wins (lets clients override Aurora defaults).
        $providerA = $this->makeProvider([
            'primary' => ['name' => 'Aurora Primary', 'description' => null, 'defaultItems' => []],
        ]);
        $providerB = $this->makeProvider([
            'primary' => ['name' => 'Client Primary', 'description' => 'Override', 'defaultItems' => []],
        ]);

        $registry = new MenuLocationRegistry([$providerA, $providerB]);

        self::assertSame('Client Primary', $registry->all()['primary']['name']);
    }

    public function testHasReturnsTrueForRegisteredLocationsAndFalseOtherwise(): void
    {
        $registry = new MenuLocationRegistry([
            $this->makeProvider([
                'primary' => ['name' => 'Primary', 'description' => null, 'defaultItems' => []],
            ]),
        ]);

        self::assertTrue($registry->has('primary'));
        self::assertFalse($registry->has('unknown'));
    }

    public function testManualRegisterAddsLocation(): void
    {
        $registry = new MenuLocationRegistry([]);

        self::assertFalse($registry->has('account'));

        $registry->register('account', 'Account menu', 'User dropdown', []);

        self::assertTrue($registry->has('account'));
        $entry = $registry->all()['account'];
        self::assertSame('Account menu', $entry['name']);
        self::assertSame('User dropdown', $entry['description']);
        self::assertSame([], $entry['defaultItems']);
    }

    public function testManualRegisterDefaultsDescriptionToNullAndItemsToEmpty(): void
    {
        $registry = new MenuLocationRegistry([]);

        $registry->register('quick', 'Quick links');

        $entry = $registry->all()['quick'];
        self::assertNull($entry['description']);
        self::assertSame([], $entry['defaultItems']);
    }

    public function testManualRegisterOverridesAProviderLocation(): void
    {
        // Same override-by-last-writer semantics as the provider iteration
        // — useful for runtime experiments without rebuilding the container.
        $registry = new MenuLocationRegistry([
            $this->makeProvider([
                'primary' => ['name' => 'Aurora Primary', 'description' => null, 'defaultItems' => []],
            ]),
        ]);

        $registry->register('primary', 'Runtime Primary');

        self::assertSame('Runtime Primary', $registry->all()['primary']['name']);
    }
}
