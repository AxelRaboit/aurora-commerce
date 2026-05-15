<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Theme\Entity\Theme;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Theme())->getId());
    }

    public function testDefaultValues(): void
    {
        $theme = new Theme();

        self::assertNull($theme->getDescription());
        self::assertFalse($theme->isActive());
        self::assertSame([], $theme->getConfig());
    }

    public function testSlugAndNameGettersAndSetters(): void
    {
        $theme = (new Theme())->setSlug('dark')->setName('Dark Theme');

        self::assertSame('dark', $theme->getSlug());
        self::assertSame('Dark Theme', $theme->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $theme = (new Theme())->setDescription('A dark theme');

        self::assertSame('A dark theme', $theme->getDescription());

        $theme->setDescription(null);
        self::assertNull($theme->getDescription());
    }

    public function testActiveGetterAndSetter(): void
    {
        $theme = (new Theme())->setActive(true);

        self::assertTrue($theme->isActive());
    }

    public function testConfigGetterAndSetter(): void
    {
        $config = ['primary' => '#000000', 'secondary' => '#ffffff'];
        $theme = (new Theme())->setConfig($config);

        self::assertSame($config, $theme->getConfig());
    }
}
