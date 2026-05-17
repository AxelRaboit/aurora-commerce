<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Menu\Serializer;

use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Serializer\MenuItemSerializer;
use Aurora\Module\Editorial\Menu\Serializer\MenuSerializer;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MenuSerializerTest extends TestCase
{
    public function testSerializeProjectsCoreFieldsAndItemCount(): void
    {
        $menu = $this->makeMenu(id: 7, name: 'Main', location: 'main', description: 'Top navigation');
        $menu->addItem($this->makeRootItem(id: 1));
        $menu->addItem($this->makeRootItem(id: 2));

        $serializer = $this->makeSerializer(registeredLocations: ['main']);

        $payload = $serializer->serialize($menu);

        self::assertSame(7, $payload['id']);
        self::assertSame('Main', $payload['name']);
        self::assertSame('main', $payload['location']);
        self::assertSame('Top navigation', $payload['description']);
        self::assertSame(2, $payload['itemCount']);
    }

    public function testProtectedFlagReflectsRegisteredLocation(): void
    {
        // A menu placed at a `location` the LocationRegistry knows about
        // is protected (theme-required slot — UI hides the delete button).
        $protectedMenu = $this->makeMenu(location: 'header');
        $freeMenu = $this->makeMenu(location: 'one-off');

        $serializer = $this->makeSerializer(registeredLocations: ['header']);

        self::assertTrue($serializer->serialize($protectedMenu)['protected']);
        self::assertFalse($serializer->serialize($freeMenu)['protected']);
    }

    public function testSerializeFullEmitsOnlyRootItemsSortedByPosition(): void
    {
        // Root items have null parent; children are serialized
        // recursively by MenuItemSerializer — so the top-level `items`
        // list must NOT include them.
        $menu = $this->makeMenu();
        $rootA = $this->makeRootItem(id: 10, position: 2);
        $rootB = $this->makeRootItem(id: 20, position: 0);
        $child = $this->makeRootItem(id: 30);
        $child->setParent($rootA); // not root
        $menu->addItem($rootA);
        $menu->addItem($rootB);
        $menu->addItem($child);

        $serializer = $this->makeSerializer();

        $payload = $serializer->serializeFull($menu);

        self::assertCount(2, $payload['items']);
        // Sorted by position: rootB (0) before rootA (2).
        self::assertSame(20, $payload['items'][0]['id']);
        self::assertSame(10, $payload['items'][1]['id']);
    }

    private function makeMenu(int $id = 1, string $name = 'menu', string $location = 'loc', ?string $description = null): Menu
    {
        $menu = new Menu();
        (new ReflectionProperty(Menu::class, 'id'))->setValue($menu, $id);
        $menu->setName($name);
        $menu->setLocation($location);
        $menu->setDescription($description);

        return $menu;
    }

    private function makeRootItem(int $id, int $position = 0): MenuItem
    {
        $item = new MenuItem();
        (new ReflectionProperty(MenuItem::class, 'id'))->setValue($item, $id);
        $item->setTargetType(MenuItemTargetTypeEnum::Home);
        $item->setVisibility(MenuItemVisibilityEnum::Always);
        $item->setPosition($position);

        return $item;
    }

    /** @param list<string> $registeredLocations */
    private function makeSerializer(array $registeredLocations = []): MenuSerializer
    {
        $registry = new MenuLocationRegistry([]);
        foreach ($registeredLocations as $location) {
            $registry->register($location, 'auto-registered');
        }

        // MenuItemSerializer needs repos + translator — we use stubs
        // because MenuSerializerTest only verifies the top-level
        // wrapping, not the per-item content (covered by
        // MenuItemSerializerTest).
        $itemSerializer = $this->makeStubItemSerializer();

        return new MenuSerializer($itemSerializer, $registry);
    }

    private function makeStubItemSerializer(): MenuItemSerializer
    {
        // Real instance with stubbed dependencies — preloadTargets
        // returns an empty cache so serialize() falls through to the
        // translator (also stubbed) for any non-Home target.
        $postRepo = $this->createStub(PostRepository::class);
        $postRepo->method('findByIds')->willReturn([]);
        $termRepo = $this->createStub(TaxonomyTermRepository::class);
        $termRepo->method('findByIds')->willReturn([]);
        $postTypeRepo = $this->createStub(PostTypeRepository::class);
        $postTypeRepo->method('findByIds')->willReturn([]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('tr');

        return new MenuItemSerializer($postRepo, $termRepo, $postTypeRepo, $translator);
    }
}
