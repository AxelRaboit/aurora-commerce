<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use PHPUnit\Framework\TestCase;

final class VaultEntryTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new VaultEntry())->getId());
    }

    public function testDefaultValues(): void
    {
        $entry = new VaultEntry();

        self::assertNull($entry->getFolder());
        self::assertNull($entry->getUrl());
        self::assertFalse($entry->isFavorite());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $entry = (new VaultEntry())->setUser($user);

        self::assertSame($user, $entry->getUser());
    }

    public function testFolderGetterAndSetter(): void
    {
        $folder = new VaultFolder();
        $entry = (new VaultEntry())->setFolder($folder);

        self::assertSame($folder, $entry->getFolder());

        $entry->setFolder(null);
        self::assertNull($entry->getFolder());
    }

    public function testTypeGetterAndSetter(): void
    {
        $entry = (new VaultEntry())->setType(VaultRecordTypeEnum::Login);

        self::assertSame(VaultRecordTypeEnum::Login, $entry->getType());
    }

    public function testTitleAndUrlGettersAndSetters(): void
    {
        $entry = (new VaultEntry())->setTitle('Bank Login')->setUrl('https://bank.example.com');

        self::assertSame('Bank Login', $entry->getTitle());
        self::assertSame('https://bank.example.com', $entry->getUrl());
    }

    public function testEncryptedDataAndIvGettersAndSetters(): void
    {
        $entry = (new VaultEntry())->setEncryptedData('cipher-text')->setIv('iv-value');

        self::assertSame('cipher-text', $entry->getEncryptedData());
        self::assertSame('iv-value', $entry->getIv());
    }

    public function testIsFavoriteGetterAndSetter(): void
    {
        $entry = (new VaultEntry())->setIsFavorite(true);

        self::assertTrue($entry->isFavorite());
    }
}
