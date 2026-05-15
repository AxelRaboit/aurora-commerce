<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Menu\Dto;

use Aurora\Core\Menu\Dto\MenuInputFactory;
use PHPUnit\Framework\TestCase;

final class MenuInputFactoryTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = (new MenuInputFactory())->fromArray([
            'name' => '  Main Menu  ',
            'location' => '  header  ',
            'description' => '  Description  ',
        ]);

        self::assertSame('Main Menu', $input->getName());
        self::assertSame('header', $input->getLocation());
        self::assertSame('Description', $input->getDescription());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new MenuInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertSame('', $input->getLocation());
        self::assertNull($input->getDescription());
    }
}
