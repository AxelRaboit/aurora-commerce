<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultFolder\Dto;

use Aurora\Module\Vault\VaultFolder\Dto\VaultFolderInput;
use PHPUnit\Framework\TestCase;

final class VaultFolderInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new VaultFolderInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getColor());
        self::assertSame(0, $input->getPosition());
        self::assertNull($input->getParentId());
    }

    public function testConstructorValues(): void
    {
        $input = new VaultFolderInput('Passwords', '#ff0000', 3, 42);

        self::assertSame('Passwords', $input->getName());
        self::assertSame('#ff0000', $input->getColor());
        self::assertSame(3, $input->getPosition());
        self::assertSame(42, $input->getParentId());
    }
}
