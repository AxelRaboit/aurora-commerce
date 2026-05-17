<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Configuration\Theme\Dto;

use Aurora\Core\Configuration\Theme\Dto\ThemeInputFactory;
use PHPUnit\Framework\TestCase;

final class ThemeInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new ThemeInputFactory())->fromArray([
            'slug' => '  DARK  ',
            'name' => '  Dark Theme  ',
            'description' => '  Description  ',
            'config' => ['primary' => '#000'],
        ]);

        self::assertSame('dark', $input->getSlug(), 'slug should be lowercased');
        self::assertSame('Dark Theme', $input->getName());
        self::assertSame('Description', $input->getDescription());
        self::assertSame(['primary' => '#000'], $input->getConfig());
    }

    public function testFromArrayWithDefaults(): void
    {
        $input = (new ThemeInputFactory())->fromArray([]);

        self::assertSame('', $input->getSlug());
        self::assertSame('', $input->getName());
        self::assertNull($input->getDescription());
        self::assertSame([], $input->getConfig());
    }

    public function testFromArrayWithNonArrayConfig(): void
    {
        $input = (new ThemeInputFactory())->fromArray(['config' => 'not-an-array']);

        self::assertSame([], $input->getConfig());
    }
}
