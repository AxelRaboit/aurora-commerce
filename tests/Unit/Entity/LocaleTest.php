<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Locale\Entity\Locale;
use PHPUnit\Framework\TestCase;

final class LocaleTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $locale = new Locale();

        self::assertFalse($locale->isDefault());
        self::assertTrue($locale->isActive());
        self::assertSame(0, $locale->getPosition());
    }

    public function testCodeGetterAndSetter(): void
    {
        $locale = (new Locale())->setCode('fr_FR');

        self::assertSame('fr_FR', $locale->getCode());
    }

    public function testNameGetterAndSetter(): void
    {
        $locale = (new Locale())->setName('French');

        self::assertSame('French', $locale->getName());
    }

    public function testIsDefaultGetterAndSetter(): void
    {
        $locale = (new Locale())->setIsDefault(true);

        self::assertTrue($locale->isDefault());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $locale = (new Locale())->setIsActive(false);

        self::assertFalse($locale->isActive());
    }

    public function testPositionGetterAndSetter(): void
    {
        $locale = (new Locale())->setPosition(3);

        self::assertSame(3, $locale->getPosition());
    }
}
