<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultEntry\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultEntry\Entity\AbstractVaultEntry;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Serializer\VaultEntrySerializer;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class VaultEntrySerializerTest extends TestCase
{
    private VaultEntrySerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new VaultEntrySerializer();
    }

    public function testSerializeProjectsAllCoreFields(): void
    {
        $entry = $this->makeEntry(
            id: 42,
            type: VaultRecordTypeEnum::Login,
            title: 'GitHub',
            url: 'https://github.com',
            encryptedData: 'base64:abc123',
            iv: 'iv-hex',
            isFavorite: true,
        );

        $payload = $this->serializer->serialize($entry);

        self::assertSame(42, $payload['id']);
        self::assertSame('login', $payload['type']);
        self::assertSame('GitHub', $payload['title']);
        self::assertSame('https://github.com', $payload['url']);
        self::assertSame('base64:abc123', $payload['encryptedData']);
        self::assertSame('iv-hex', $payload['iv']);
        self::assertTrue($payload['isFavorite']);
    }

    public function testSerializeReturnsCipherTextNeverPlaintext(): void
    {
        // Sanity check: the serializer must NOT expose any plaintext
        // password field. The entity only carries encryptedData + iv —
        // the front decrypts with the user's master key. This test would
        // catch a refactor that accidentally added a `password` accessor.
        $entry = $this->makeEntry(id: 1, encryptedData: 'gibberish-ciphertext', iv: 'iv-1');

        $payload = $this->serializer->serialize($entry);

        self::assertArrayNotHasKey('password', $payload);
        self::assertArrayNotHasKey('plaintext', $payload);
        self::assertArrayNotHasKey('decrypted', $payload);
        self::assertSame('gibberish-ciphertext', $payload['encryptedData']);
    }

    public function testSerializeEmitsNullFolderFieldsWhenEntryHasNoFolder(): void
    {
        $entry = $this->makeEntry(id: 1);

        $payload = $this->serializer->serialize($entry);

        self::assertNull($payload['folderId']);
        self::assertNull($payload['folderName']);
        self::assertNull($payload['folderColor']);
    }

    public function testSerializeIncludesFolderMetadataWhenAttached(): void
    {
        $folder = $this->makeFolder(id: 9, name: 'Work', color: '#ff0066');
        $entry = $this->makeEntry(id: 1);
        $entry->setFolder($folder);

        $payload = $this->serializer->serialize($entry);

        self::assertSame(9, $payload['folderId']);
        self::assertSame('Work', $payload['folderName']);
        self::assertSame('#ff0066', $payload['folderColor']);
    }

    public function testSerializeFormatsTimestampsAsAtom(): void
    {
        $entry = $this->makeEntry();

        $payload = $this->serializer->serialize($entry);

        self::assertSame('2026-01-15T10:30:00+00:00', $payload['createdAt']);
        self::assertSame('2026-01-15T11:00:00+00:00', $payload['updatedAt']);
    }

    private function makeEntry(
        int $id = 1,
        VaultRecordTypeEnum $type = VaultRecordTypeEnum::Login,
        string $title = 'entry',
        ?string $url = null,
        string $encryptedData = 'cipher',
        string $iv = 'iv',
        bool $isFavorite = false,
    ): VaultEntry {
        $entry = new VaultEntry();
        (new ReflectionProperty(VaultEntry::class, 'id'))->setValue($entry, $id);
        $entry->setUser(new User());
        $entry->setType($type);
        $entry->setTitle($title);
        $entry->setUrl($url);
        $entry->setEncryptedData($encryptedData);
        $entry->setIv($iv);
        $entry->setIsFavorite($isFavorite);

        (new ReflectionProperty(AbstractVaultEntry::class, 'createdAt'))->setValue($entry, new DateTimeImmutable('2026-01-15T10:30:00+00:00'));
        (new ReflectionProperty(AbstractVaultEntry::class, 'updatedAt'))->setValue($entry, new DateTimeImmutable('2026-01-15T11:00:00+00:00'));

        return $entry;
    }

    private function makeFolder(int $id, string $name, ?string $color = null): VaultFolder
    {
        $folder = new VaultFolder();
        (new ReflectionProperty(VaultFolder::class, 'id'))->setValue($folder, $id);
        $folder->setUser(new User());
        $folder->setName($name);
        $folder->setColor($color);

        return $folder;
    }
}
