<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Menu\Entity\MenuItemInterface;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use PHPUnit\Framework\TestCase;

final class MenuItemTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new MenuItemTranslation())->getId());
    }

    public function testLabelIsNullByDefault(): void
    {
        self::assertNull((new MenuItemTranslation())->getLabel());
    }

    public function testMenuItemGetterAndSetter(): void
    {
        $item = $this->createStub(MenuItemInterface::class);
        $translation = (new MenuItemTranslation())->setMenuItem($item);

        self::assertSame($item, $translation->getMenuItem());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new MenuItemTranslation())->setLocale('en');

        self::assertSame('en', $translation->getLocale());
    }

    public function testLabelGetterAndSetter(): void
    {
        $translation = (new MenuItemTranslation())->setLabel('Home');

        self::assertSame('Home', $translation->getLabel());

        $translation->setLabel(null);
        self::assertNull($translation->getLabel());
    }
}
