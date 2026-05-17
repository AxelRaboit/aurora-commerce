<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Manager;

use Aurora\Module\Editorial\Menu\Dto\MenuInput;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInput;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Manager\MenuManager;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
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

    private function menu(string $name, string $location, ?string $description = null): MenuInput
    {
        return new MenuInput(name: $name, location: $location, description: $description);
    }

    /**
     * @param array<string, ?string> $translations
     */
    private function item(
        ?MenuItemTargetTypeEnum $targetType,
        ?int $targetId = null,
        ?string $customUrl = null,
        ?int $parentId = null,
        bool $openInNewTab = false,
        ?string $cssClass = null,
        MenuItemVisibilityEnum $visibility = MenuItemVisibilityEnum::Always,
        array $translations = [],
    ): MenuItemInput {
        return new MenuItemInput(
            targetType: $targetType,
            targetId: $targetId,
            customUrl: $customUrl,
            parentId: $parentId,
            openInNewTab: $openInNewTab,
            cssClass: $cssClass,
            visibility: $visibility,
            translations: $translations,
        );
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function testCreateMenu(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test', 'Main navigation'));

        self::assertNotNull($menu->getId());
        self::assertSame('Header', $menu->getName());
        self::assertSame('custom-test', $menu->getLocation());
        self::assertSame('Main navigation', $menu->getDescription());
    }

    public function testCreateMenuDuplicateLocationThrows(): void
    {
        $this->manager->create($this->menu('Header', 'custom-test'));
        $this->expectException(InvalidArgumentException::class);
        $this->manager->create($this->menu('Header 2', 'custom-test'));
    }

    public function testUpdateMenu(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $this->manager->update($menu, $this->menu('Top Header', 'top-test', 'Updated'));

        self::assertSame('Top Header', $menu->getName());
        self::assertSame('top-test', $menu->getLocation());
        self::assertSame('Updated', $menu->getDescription());
    }

    public function testCannotDeleteProtectedMenu(): void
    {
        $menu = $this->manager->create($this->menu('Primary', 'primary'));
        self::assertTrue($this->manager->isProtected($menu));

        $this->expectException(InvalidArgumentException::class);
        $this->manager->delete($menu);
    }

    public function testCannotChangeLocationOfProtectedMenu(): void
    {
        $menu = $this->manager->create($this->menu('Primary', 'primary'));

        $this->expectException(InvalidArgumentException::class);
        $this->manager->update($menu, $this->menu('Primary Renamed', 'something-else'));
    }

    public function testCanRenameProtectedMenu(): void
    {
        $menu = $this->manager->create($this->menu('Primary', 'primary'));
        $this->manager->update($menu, $this->menu('Renamed', 'primary', 'Updated'));

        self::assertSame('Renamed', $menu->getName());
        self::assertSame('primary', $menu->getLocation());
        self::assertSame('Updated', $menu->getDescription());
    }

    public function testDeleteMenuCascadesItems(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));
        $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontLogin));

        $menuId = $menu->getId();
        $this->manager->delete($menu);

        self::assertNull($this->menuRepository->find($menuId));
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function testCreateItemWithCustomUrl(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $item = $this->manager->createItem($menu, $this->item(
            targetType: MenuItemTargetTypeEnum::CustomUrl,
            customUrl: 'https://example.com',
            openInNewTab: true,
            cssClass: 'btn',
        ));

        self::assertSame(MenuItemTargetTypeEnum::CustomUrl, $item->getTargetType());
        self::assertSame('https://example.com', $item->getCustomUrl());
        self::assertTrue($item->isOpenInNewTab());
        self::assertSame('btn', $item->getCssClass());
        self::assertSame(0, $item->getPosition());
    }

    public function testCreateCustomUrlItemWithoutUrlThrows(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::CustomUrl));
    }

    public function testCreatePostItemWithoutTargetIdThrows(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Post));
    }

    public function testItemPositionIncrementsPerParent(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $item1 = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));
        $item2 = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontLogin));
        $child1 = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontRegister, parentId: $item1->getId()));
        $child2 = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontAccount, parentId: $item1->getId()));

        self::assertSame(0, $item1->getPosition());
        self::assertSame(1, $item2->getPosition());
        self::assertSame(0, $child1->getPosition());
        self::assertSame(1, $child2->getPosition());
        self::assertSame($item1->getId(), $child1->getParent()?->getId());
    }

    public function testCreateItemRejectsParentFromOtherMenu(): void
    {
        $menuA = $this->manager->create($this->menu('A', 'a'));
        $menuB = $this->manager->create($this->menu('B', 'b'));
        $itemA = $this->manager->createItem($menuA, $this->item(MenuItemTargetTypeEnum::Home));

        $this->expectException(InvalidArgumentException::class);
        $this->manager->createItem($menuB, $this->item(MenuItemTargetTypeEnum::Home, parentId: $itemA->getId()));
    }

    public function testUpdateItem(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $item = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));

        $this->manager->updateItem($item, $this->item(
            targetType: MenuItemTargetTypeEnum::CustomUrl,
            customUrl: '/about',
            visibility: MenuItemVisibilityEnum::AuthenticatedOnly,
        ));

        self::assertSame(MenuItemTargetTypeEnum::CustomUrl, $item->getTargetType());
        self::assertSame('/about', $item->getCustomUrl());
        self::assertSame(MenuItemVisibilityEnum::AuthenticatedOnly, $item->getVisibility());
    }

    public function testDeleteItemRemovesChildren(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $parent = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));
        $child = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontLogin, parentId: $parent->getId()));
        $childId = $child->getId();

        $this->manager->deleteItem($parent);
        $this->entityManager->clear();

        $remaining = $this->entityManager->getRepository(MenuItem::class)->find($childId);
        self::assertNull($remaining);
    }

    // ── Reorder ───────────────────────────────────────────────────────────────

    public function testReorderItems(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $a = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));
        $b = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontLogin));
        $c = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontRegister));

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
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $a = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));
        $b = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::FrontLogin, parentId: $a->getId()));

        $this->expectException(InvalidArgumentException::class);
        $this->manager->reorderItems($menu, [
            ['id' => $a->getId(), 'parentId' => $b->getId(), 'position' => 0],
        ]);
    }

    // ── Translations ──────────────────────────────────────────────────────────

    public function testSetAndUpdateTranslation(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $item = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));

        $this->manager->setTranslation($item, 'fr', 'Accueil');
        $this->manager->setTranslation($item, 'en', 'Home');

        self::assertSame('Accueil', $item->getTranslation('fr')?->getLabel());
        self::assertSame('Home', $item->getTranslation('en')?->getLabel());

        $this->manager->setTranslation($item, 'fr', "Page d'accueil");
        self::assertSame("Page d'accueil", $item->getTranslation('fr')?->getLabel());
    }

    public function testSetTranslationWithEmptyRemovesIt(): void
    {
        $menu = $this->manager->create($this->menu('Header', 'custom-test'));
        $item = $this->manager->createItem($menu, $this->item(MenuItemTargetTypeEnum::Home));

        $this->manager->setTranslation($item, 'fr', 'Accueil');
        self::assertNotNull($item->getTranslation('fr'));

        $this->manager->setTranslation($item, 'fr', null);
        self::assertNull($item->getTranslation('fr'));

        $this->manager->setTranslation($item, 'en', 'Home');
        $this->manager->setTranslation($item, 'en', '   ');
        self::assertNull($item->getTranslation('en'));
    }
}
