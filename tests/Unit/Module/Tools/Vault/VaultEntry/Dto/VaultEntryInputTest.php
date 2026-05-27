<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools\Vault\VaultEntry\Dto;

use Aurora\Module\Tools\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Tools\Vault\VaultEntry\Dto\VaultEntryInput;
use PHPUnit\Framework\TestCase;

final class VaultEntryInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new VaultEntryInput();

        self::assertSame(VaultRecordTypeEnum::Login, $input->getType());
        self::assertSame('', $input->getTitle());
        self::assertNull($input->getUrl());
        self::assertSame('', $input->getEncryptedData());
        self::assertSame('', $input->getIv());
        self::assertNull($input->getFolderId());
        self::assertFalse($input->isFavorite());
    }

    public function testConstructorValues(): void
    {
        $input = new VaultEntryInput(
            type: VaultRecordTypeEnum::SecureNote,
            title: 'Bank',
            url: 'https://bank.com',
            encryptedData: 'cipher',
            iv: 'iv-value',
            folderId: 42,
            isFavorite: true,
        );

        self::assertSame(VaultRecordTypeEnum::SecureNote, $input->getType());
        self::assertSame('Bank', $input->getTitle());
        self::assertSame('https://bank.com', $input->getUrl());
        self::assertSame('cipher', $input->getEncryptedData());
        self::assertSame('iv-value', $input->getIv());
        self::assertSame(42, $input->getFolderId());
        self::assertTrue($input->isFavorite());
    }
}
