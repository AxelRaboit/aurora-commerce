<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Tools\Vault\VaultEntry\Dto;

use Aurora\Module\Tools\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Tools\Vault\VaultEntry\Dto\VaultEntryInputFactory;
use PHPUnit\Framework\TestCase;

final class VaultEntryInputFactoryTest extends TestCase
{
    private VaultEntryInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new VaultEntryInputFactory();
    }

    public function testFullPayloadHydratesEveryField(): void
    {
        $input = $this->factory->fromArray([
            'type' => '  login ',
            'title' => '  GitHub  ',
            'url' => '  https://github.com  ',
            'encryptedData' => '  base64-ciphertext  ',
            'iv' => '  iv-hex  ',
            'folderId' => '7',
            'isFavorite' => true,
        ]);

        self::assertSame(VaultRecordTypeEnum::Login, $input->getType());
        self::assertSame('GitHub', $input->getTitle());
        self::assertSame('https://github.com', $input->getUrl());
        self::assertSame('base64-ciphertext', $input->getEncryptedData());
        self::assertSame('iv-hex', $input->getIv());
        self::assertSame(7, $input->getFolderId());
        self::assertTrue($input->isFavorite());
    }

    public function testUnknownTypeFallsBackToLogin(): void
    {
        // Defensive default: a typo in the payload (or a value from a
        // future enum case) must not crash the factory.
        $input = $this->factory->fromArray(['type' => 'not-a-real-type']);

        self::assertSame(VaultRecordTypeEnum::Login, $input->getType());
    }

    public function testEmptyArrayProducesSafeDefaults(): void
    {
        // Validation will reject empty title/encryptedData/iv later via
        // PayloadValidator — the factory's job is just to bind, not
        // to reject.
        $input = $this->factory->fromArray([]);

        self::assertSame(VaultRecordTypeEnum::Login, $input->getType());
        self::assertSame('', $input->getTitle());
        self::assertNull($input->getUrl());
        self::assertSame('', $input->getEncryptedData());
        self::assertSame('', $input->getIv());
        self::assertNull($input->getFolderId());
        self::assertFalse($input->isFavorite());
    }

    public function testEmptyOrWhitespaceUrlBecomesNull(): void
    {
        self::assertNull($this->factory->fromArray(['url' => ''])->getUrl());
        self::assertNull($this->factory->fromArray(['url' => '   '])->getUrl());
    }

    public function testIsFavoriteAcceptsTruthyAndFalsyValues(): void
    {
        // The front may send '1' / 0 / true / false depending on the
        // serializer — all coerce via (bool).
        self::assertTrue($this->factory->fromArray(['isFavorite' => '1'])->isFavorite());
        self::assertTrue($this->factory->fromArray(['isFavorite' => 1])->isFavorite());
        self::assertFalse($this->factory->fromArray(['isFavorite' => 0])->isFavorite());
        self::assertFalse($this->factory->fromArray(['isFavorite' => ''])->isFavorite());
    }

    public function testFolderIdIsCoercedFromString(): void
    {
        // JSON payloads often deliver ids as strings (form data, legacy
        // clients). Factory casts so the Manager always sees a real int.
        self::assertSame(42, $this->factory->fromArray(['folderId' => '42'])->getFolderId());
    }
}
