<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools\Vault\VaultFolder\Dto;

use Aurora\Module\Tools\Vault\VaultFolder\Dto\VaultFolderInputFactory;
use PHPUnit\Framework\TestCase;

final class VaultFolderInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new VaultFolderInputFactory())->fromArray([
            'name' => '  Passwords  ',
            'color' => '  #ff0000  ',
            'position' => '5',
            'parentId' => '42',
        ]);

        self::assertSame('Passwords', $input->getName());
        self::assertSame('#ff0000', $input->getColor());
        self::assertSame(5, $input->getPosition());
        self::assertSame(42, $input->getParentId());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new VaultFolderInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getColor());
        self::assertSame(0, $input->getPosition());
        self::assertNull($input->getParentId());
    }
}
