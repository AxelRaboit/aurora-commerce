<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Configuration\Theme\Dto;

use Aurora\Module\Configuration\Theme\Dto\ThemeInput;
use PHPUnit\Framework\TestCase;

final class ThemeInputTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $config = ['primary' => '#000000'];
        $input = new ThemeInput('dark', 'Dark Theme', 'A dark variant', $config);

        self::assertSame('dark', $input->getSlug());
        self::assertSame('Dark Theme', $input->getName());
        self::assertSame('A dark variant', $input->getDescription());
        self::assertSame($config, $input->getConfig());
    }

    public function testNullDescription(): void
    {
        $input = new ThemeInput('light', 'Light', null, []);

        self::assertNull($input->getDescription());
    }
}
