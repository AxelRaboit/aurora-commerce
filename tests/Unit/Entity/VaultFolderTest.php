<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use PHPUnit\Framework\TestCase;

final class VaultFolderTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new VaultFolder())->getId());
    }

    public function testDefaultValues(): void
    {
        $folder = new VaultFolder();

        self::assertNull($folder->getColor());
        self::assertSame(0, $folder->getPosition());
        self::assertNull($folder->getParent());
    }

    public function testNameGetterAndSetter(): void
    {
        $folder = (new VaultFolder())->setName('Passwords');

        self::assertSame('Passwords', $folder->getName());
    }

    public function testColorGetterAndSetter(): void
    {
        $folder = (new VaultFolder())->setColor('#ff0000');

        self::assertSame('#ff0000', $folder->getColor());

        $folder->setColor(null);
        self::assertNull($folder->getColor());
    }

    public function testPositionGetterAndSetter(): void
    {
        $folder = (new VaultFolder())->setPosition(5);

        self::assertSame(5, $folder->getPosition());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $folder = (new VaultFolder())->setUser($user);

        self::assertSame($user, $folder->getUser());
    }

    public function testParentGetterAndSetter(): void
    {
        $parent = new VaultFolder();
        $folder = (new VaultFolder())->setParent($parent);

        self::assertSame($parent, $folder->getParent());

        $folder->setParent(null);
        self::assertNull($folder->getParent());
    }
}
