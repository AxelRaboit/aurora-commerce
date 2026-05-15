<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItemInterface;
use PHPUnit\Framework\TestCase;

final class MenuTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Menu())->getId());
    }

    public function testItemsCollectionInitialized(): void
    {
        self::assertCount(0, (new Menu())->getItems());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new Menu())->getDescription());
    }

    public function testNameAndLocationGettersAndSetters(): void
    {
        $menu = (new Menu())->setName('Main Menu')->setLocation('header');

        self::assertSame('Main Menu', $menu->getName());
        self::assertSame('header', $menu->getLocation());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $menu = (new Menu())->setDescription('Top navigation');

        self::assertSame('Top navigation', $menu->getDescription());

        $menu->setDescription(null);
        self::assertNull($menu->getDescription());
    }

    public function testAddItemIgnoresDuplicate(): void
    {
        $menu = new Menu();
        $item = $this->createStub(MenuItemInterface::class);

        $menu->addItem($item);
        $menu->addItem($item);

        self::assertCount(1, $menu->getItems());
    }

    public function testRemoveItem(): void
    {
        $menu = new Menu();
        $item = $this->createStub(MenuItemInterface::class);

        $menu->addItem($item);
        $menu->removeItem($item);

        self::assertCount(0, $menu->getItems());
    }
}
