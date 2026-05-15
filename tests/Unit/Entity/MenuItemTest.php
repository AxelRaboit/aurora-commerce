<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use PHPUnit\Framework\TestCase;

final class MenuItemTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new MenuItem())->getId());
    }

    public function testCollectionsInitialized(): void
    {
        $item = new MenuItem();

        self::assertCount(0, $item->getChildren());
        self::assertCount(0, $item->getTranslations());
    }

    public function testDefaultValues(): void
    {
        $item = new MenuItem();

        self::assertNull($item->getReference());
        self::assertSame(MenuItemTargetTypeEnum::CustomUrl, $item->getTargetType());
        self::assertNull($item->getTargetId());
        self::assertNull($item->getCustomUrl());
        self::assertFalse($item->isOpenInNewTab());
        self::assertNull($item->getCssClass());
        self::assertSame(MenuItemVisibilityEnum::Always, $item->getVisibility());
        self::assertSame(0, $item->getPosition());
        self::assertNull($item->getParent());
    }

    public function testTargetTypeAndIdGettersAndSetters(): void
    {
        $item = (new MenuItem())->setTargetType(MenuItemTargetTypeEnum::Post)->setTargetId(42);

        self::assertSame(MenuItemTargetTypeEnum::Post, $item->getTargetType());
        self::assertSame(42, $item->getTargetId());
    }

    public function testCustomUrlGetterAndSetter(): void
    {
        $item = (new MenuItem())->setCustomUrl('https://example.com');

        self::assertSame('https://example.com', $item->getCustomUrl());
    }

    public function testOpenInNewTabGetterAndSetter(): void
    {
        $item = (new MenuItem())->setOpenInNewTab(true);

        self::assertTrue($item->isOpenInNewTab());
    }

    public function testCssClassGetterAndSetter(): void
    {
        $item = (new MenuItem())->setCssClass('btn btn-primary');

        self::assertSame('btn btn-primary', $item->getCssClass());
    }

    public function testVisibilityGetterAndSetter(): void
    {
        $item = (new MenuItem())->setVisibility(MenuItemVisibilityEnum::AuthenticatedOnly);

        self::assertSame(MenuItemVisibilityEnum::AuthenticatedOnly, $item->getVisibility());
    }

    public function testPositionGetterAndSetter(): void
    {
        $item = (new MenuItem())->setPosition(5);

        self::assertSame(5, $item->getPosition());
    }

    public function testParentGetterAndSetter(): void
    {
        $parent = new MenuItem();
        $item = (new MenuItem())->setParent($parent);

        self::assertSame($parent, $item->getParent());

        $item->setParent(null);
        self::assertNull($item->getParent());
    }

    public function testMenuGetterAndSetter(): void
    {
        $menu = new Menu();
        $item = (new MenuItem())->setMenu($menu);

        self::assertSame($menu, $item->getMenu());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $item = (new MenuItem())->setReference('MI-001');

        self::assertSame('MI-001', $item->getReference());
    }

    public function testAddTranslationIndexedByLocale(): void
    {
        $item = new MenuItem();
        $translation = (new MenuItemTranslation())->setLocale('fr')->setLabel('Accueil');

        $item->addTranslation($translation);

        self::assertCount(1, $item->getTranslations());
        self::assertSame($translation, $item->getTranslation('fr'));
        self::assertSame($item, $translation->getMenuItem());
    }

    public function testRemoveTranslation(): void
    {
        $item = new MenuItem();
        $translation = (new MenuItemTranslation())->setLocale('fr')->setLabel('Accueil');

        $item->addTranslation($translation);
        $item->removeTranslation($translation);

        self::assertCount(0, $item->getTranslations());
    }

    public function testGetTranslationReturnsNullForMissingLocale(): void
    {
        self::assertNull((new MenuItem())->getTranslation('de'));
    }
}
