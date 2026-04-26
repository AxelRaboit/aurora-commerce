<?php

declare(strict_types=1);

namespace App\Tests\Integration\Manager;

use App\Entity\MenuItem;
use App\Enum\MenuItemTargetTypeEnum;
use App\Enum\MenuItemVisibilityEnum;
use App\Manager\MenuManager;
use App\Repository\MenuRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class MenuManagerTest extends IntegrationTestCase
{
    private MenuManager $manager;
    private MenuRepository $menuRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::getContainer()->get(MenuManager::class);
        $this->menuRepository = static::getContainer()->get(MenuRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean menus between tests
        foreach ($this->menuRepository->findAll() as $menu) {
            $this->entityManager->remove($menu);
        }
        $this->entityManager->flush();
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function testCreateMenu(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test', 'Main navigation');

        self::assertNotNull($menu->getId());
        self::assertSame('Header', $menu->getName());
        self::assertSame('custom-test', $menu->getLocation());
        self::assertSame('Main navigation', $menu->getDescription());
    }

    public function testCreateMenuWithEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createMenu('  ', 'custom-test');
    }

    public function testCreateMenuWithInvalidLocationThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createMenu('Header', 'Primary Header'); // spaces + uppercase
    }

    public function testCreateMenuDuplicateLocationThrows(): void
    {
        $this->manager->createMenu('Header', 'custom-test');
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createMenu('Header 2', 'custom-test');
    }

    public function testUpdateMenu(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $this->manager->updateMenu($menu, 'Top Header', 'top-test', 'Updated');

        self::assertSame('Top Header', $menu->getName());
        self::assertSame('top-test', $menu->getLocation());
        self::assertSame('Updated', $menu->getDescription());
    }

    public function testCannotDeleteProtectedMenu(): void
    {
        $menu = $this->manager->createMenu('Primary', 'primary');
        self::assertTrue($this->manager->isProtected($menu));

        $this->expectException(InvalidArgumentException::class);
        $this->manager->deleteMenu($menu);
    }

    public function testCannotChangeLocationOfProtectedMenu(): void
    {
        $menu = $this->manager->createMenu('Primary', 'primary');

        $this->expectException(InvalidArgumentException::class);
        $this->manager->updateMenu($menu, 'Primary Renamed', 'something-else');
    }

    public function testCanRenameProtectedMenu(): void
    {
        $menu = $this->manager->createMenu('Primary', 'primary');
        $this->manager->updateMenu($menu, 'Renamed', 'primary', 'Updated');

        self::assertSame('Renamed', $menu->getName());
        self::assertSame('primary', $menu->getLocation());
        self::assertSame('Updated', $menu->getDescription());
    }

    public function testDeleteMenuCascadesItems(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin);

        $menuId = $menu->getId();
        $this->manager->deleteMenu($menu);

        self::assertNull($this->menuRepository->find($menuId));
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function testCreateItemWithCustomUrl(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $item = $this->manager->createItem($menu, MenuItemTargetTypeEnum::CustomUrl, null, [
            'customUrl' => 'https://example.com',
            'openInNewTab' => true,
            'cssClass' => 'btn',
        ]);

        self::assertSame(MenuItemTargetTypeEnum::CustomUrl, $item->getTargetType());
        self::assertSame('https://example.com', $item->getCustomUrl());
        self::assertTrue($item->isOpenInNewTab());
        self::assertSame('btn', $item->getCssClass());
        self::assertSame(0, $item->getPosition());
    }

    public function testCreateCustomUrlItemWithoutUrlThrows(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::CustomUrl);
    }

    public function testCreatePostItemWithoutTargetIdThrows(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Post);
    }

    public function testItemPositionIncrementsPerParent(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $item1 = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $item2 = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin);
        $child1 = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontRegister, null, ['parentId' => $item1->getId()]);
        $child2 = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontAccount, null, ['parentId' => $item1->getId()]);

        self::assertSame(0, $item1->getPosition());
        self::assertSame(1, $item2->getPosition());
        self::assertSame(0, $child1->getPosition());
        self::assertSame(1, $child2->getPosition());
        self::assertSame($item1->getId(), $child1->getParent()?->getId());
    }

    public function testCreateItemRejectsParentFromOtherMenu(): void
    {
        $menuA = $this->manager->createMenu('A', 'a');
        $menuB = $this->manager->createMenu('B', 'b');
        $itemA = $this->manager->createItem($menuA, MenuItemTargetTypeEnum::Home);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menuB, MenuItemTargetTypeEnum::Home, null, ['parentId' => $itemA->getId()]);
    }

    public function testUpdateItem(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $item = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);

        $this->manager->updateItem($item, MenuItemTargetTypeEnum::CustomUrl, null, [
            'customUrl' => '/about',
            'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly,
        ]);

        self::assertSame(MenuItemTargetTypeEnum::CustomUrl, $item->getTargetType());
        self::assertSame('/about', $item->getCustomUrl());
        self::assertSame(MenuItemVisibilityEnum::AuthenticatedOnly, $item->getVisibility());
    }

    public function testDeleteItemRemovesChildren(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $parent = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $child = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin, null, ['parentId' => $parent->getId()]);
        $childId = $child->getId();

        $this->manager->deleteItem($parent);
        $this->entityManager->clear();

        $remaining = $this->entityManager->getRepository(MenuItem::class)->find($childId);
        self::assertNull($remaining);
    }

    // ── Reorder ───────────────────────────────────────────────────────────────

    public function testReorderItems(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $a = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $b = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin);
        $c = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontRegister);

        $this->manager->reorderItems($menu, [
            ['id' => $c->getId(), 'parentId' => null, 'position' => 0],
            ['id' => $a->getId(), 'parentId' => null, 'position' => 1],
            ['id' => $b->getId(), 'parentId' => $a->getId(), 'position' => 0],
        ]);

        $this->entityManager->refresh($a);
        $this->entityManager->refresh($b);
        $this->entityManager->refresh($c);

        self::assertSame(0, $c->getPosition());
        self::assertNull($c->getParent());
        self::assertSame(1, $a->getPosition());
        self::assertSame(0, $b->getPosition());
        self::assertSame($a->getId(), $b->getParent()?->getId());
    }

    public function testReorderRejectsCycle(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $a = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $b = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin, null, ['parentId' => $a->getId()]);

        $this->expectException(InvalidArgumentException::class);
        // Try to make a child of b -> impossible (would create cycle a -> b -> a)
        $this->manager->reorderItems($menu, [
            ['id' => $a->getId(), 'parentId' => $b->getId(), 'position' => 0],
        ]);
    }

    // ── Translations ──────────────────────────────────────────────────────────

    public function testSetAndUpdateTranslation(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $item = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);

        $this->manager->setTranslation($item, 'fr', 'Accueil');
        $this->manager->setTranslation($item, 'en', 'Home');

        self::assertSame('Accueil', $item->getTranslation('fr')?->getLabel());
        self::assertSame('Home', $item->getTranslation('en')?->getLabel());

        // Update existing
        $this->manager->setTranslation($item, 'fr', "Page d'accueil");
        self::assertSame("Page d'accueil", $item->getTranslation('fr')?->getLabel());
    }

    public function testSetTranslationWithEmptyRemovesIt(): void
    {
        $menu = $this->manager->createMenu('Header', 'custom-test');
        $item = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);

        $this->manager->setTranslation($item, 'fr', 'Accueil');
        self::assertNotNull($item->getTranslation('fr'));

        $this->manager->setTranslation($item, 'fr', null);
        self::assertNull($item->getTranslation('fr'));

        // Setting empty string also removes
        $this->manager->setTranslation($item, 'en', 'Home');
        $this->manager->setTranslation($item, 'en', '   ');
        self::assertNull($item->getTranslation('en'));
    }
}
