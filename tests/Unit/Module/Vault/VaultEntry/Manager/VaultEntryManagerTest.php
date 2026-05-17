<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultEntry\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultEntry\Dto\VaultEntryInput;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Manager\VaultEntryManager;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class VaultEntryManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private VaultFolderRepository $folderRepository;
    private VaultEntryManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->folderRepository = $this->createMock(VaultFolderRepository::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new VaultEntryManager($this->entityManager, $auditLogger, $this->folderRepository);
    }

    public function testCreateAssignsUserAndPersists(): void
    {
        $user = $this->makeUser();
        $input = new VaultEntryInput(
            type: VaultRecordTypeEnum::Login,
            title: 'GitHub',
            url: 'https://github.com',
            encryptedData: 'cipher',
            iv: 'iv',
        );

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $entry = $this->manager->create($user, $input);

        self::assertSame($user, $entry->getUser());
        self::assertSame(VaultRecordTypeEnum::Login, $entry->getType());
        self::assertSame('GitHub', $entry->getTitle());
        self::assertSame('cipher', $entry->getEncryptedData());
        self::assertSame('iv', $entry->getIv());
        self::assertFalse($entry->isFavorite());
        self::assertNull($entry->getFolder());
    }

    public function testCreateResolvesFolderViaUserScopedLookup(): void
    {
        // Folder ID in input must be looked up via
        // `findOneByUserAndId` — never `find()` — so the user can't
        // attach to a folder owned by another user.
        $user = $this->makeUser(id: 5);
        $folder = $this->makeFolder(id: 9);

        $this->folderRepository->expects(self::once())
            ->method('findOneByUserAndId')
            ->with($user, 9)
            ->willReturn($folder);

        $input = new VaultEntryInput(title: 'X', encryptedData: 'c', iv: 'i', folderId: 9);

        $entry = $this->manager->create($user, $input);

        self::assertSame($folder, $entry->getFolder());
    }

    public function testCreateLeavesFolderNullWhenFolderIdAbsent(): void
    {
        $user = $this->makeUser();
        $this->folderRepository->expects(self::never())->method('findOneByUserAndId');

        $entry = $this->manager->create($user, new VaultEntryInput(title: 'X', encryptedData: 'c', iv: 'i'));

        self::assertNull($entry->getFolder());
    }

    public function testCreateLeavesFolderNullWhenFolderIdRefersToOtherUser(): void
    {
        // findOneByUserAndId returns null → entry stays folder-less
        // (no exception — the front handled the validation).
        $user = $this->makeUser();
        $this->folderRepository->method('findOneByUserAndId')->willReturn(null);

        $entry = $this->manager->create($user, new VaultEntryInput(title: 'X', encryptedData: 'c', iv: 'i', folderId: 999));

        self::assertNull($entry->getFolder());
    }

    public function testUpdateRehydratesAllFieldsAndFlushes(): void
    {
        $entry = $this->makeEntry(id: 1);
        $entry->setUser($this->makeUser());
        $entry->setTitle('old');

        $input = new VaultEntryInput(
            type: VaultRecordTypeEnum::Card,
            title: 'new',
            url: 'https://example.com',
            encryptedData: 'new-cipher',
            iv: 'new-iv',
            isFavorite: true,
        );

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($entry, $input);

        self::assertSame(VaultRecordTypeEnum::Card, $entry->getType());
        self::assertSame('new', $entry->getTitle());
        self::assertSame('https://example.com', $entry->getUrl());
        self::assertSame('new-cipher', $entry->getEncryptedData());
        self::assertSame('new-iv', $entry->getIv());
        self::assertTrue($entry->isFavorite());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $entry = $this->makeEntry();
        $entry->setUser($this->makeUser());

        $this->entityManager->expects(self::once())->method('remove')->with($entry);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($entry);
    }

    public function testToggleFavoriteFlipsTheFlag(): void
    {
        $entry = $this->makeEntry();
        $entry->setIsFavorite(false);

        $this->entityManager->expects(self::exactly(2))->method('flush');

        $this->manager->toggleFavorite($entry);
        self::assertTrue($entry->isFavorite());

        $this->manager->toggleFavorite($entry);
        self::assertFalse($entry->isFavorite());
    }

    public function testMoveUpdatesFolderAndFlushes(): void
    {
        $entry = $this->makeEntry();
        $folder = $this->makeFolder(id: 2);

        $this->entityManager->expects(self::once())->method('flush');

        $this->manager->move($entry, $folder);

        self::assertSame($folder, $entry->getFolder());
    }

    public function testMoveToRootUnassignsFolder(): void
    {
        $entry = $this->makeEntry();
        $entry->setFolder($this->makeFolder(id: 2));

        $this->manager->move($entry, null);

        self::assertNull($entry->getFolder());
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeEntry(int $id = 1): VaultEntry
    {
        $entry = new VaultEntry();
        (new ReflectionProperty(VaultEntry::class, 'id'))->setValue($entry, $id);
        $entry->setType(VaultRecordTypeEnum::Login);
        $entry->setTitle('e');
        $entry->setEncryptedData('c');
        $entry->setIv('i');

        return $entry;
    }

    private function makeFolder(int $id): VaultFolder
    {
        $folder = new VaultFolder();
        (new ReflectionProperty(VaultFolder::class, 'id'))->setValue($folder, $id);
        $folder->setUser($this->makeUser());
        $folder->setName('F');

        return $folder;
    }
}
