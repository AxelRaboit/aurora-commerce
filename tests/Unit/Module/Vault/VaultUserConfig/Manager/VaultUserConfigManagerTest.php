<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultUserConfig\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Repository\VaultEntryRepository;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInput;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Vault\VaultUserConfig\Manager\VaultUserConfigManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class VaultUserConfigManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private VaultEntryRepository $entryRepository;
    private VaultFolderRepository $folderRepository;
    private VaultUserConfigManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entryRepository = $this->createMock(VaultEntryRepository::class);
        $this->folderRepository = $this->createStub(VaultFolderRepository::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new VaultUserConfigManager(
            $this->entityManager,
            $auditLogger,
            $this->entryRepository,
            $this->folderRepository,
        );
    }

    public function testSetupPersistsConfigWithSalt(): void
    {
        $user = $this->makeUser();
        $input = new VaultUserConfigInput(argon2Salt: 'a'.str_repeat('b', 31));

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $config = $this->manager->setup($user, $input);

        self::assertSame($user, $config->getUser());
        self::assertSame('a'.str_repeat('b', 31), $config->getArgon2Salt());
    }

    public function testChangeMasterPasswordRotatesSaltAndReencryptsEachEntry(): void
    {
        // The user-side flow: user enters new master password, the front
        // re-encrypts every entry's payload with the new key, then ships
        // {id, encryptedData, iv} for each. The manager must update the
        // salt + re-write every entry's ciphertext atomically.
        $user = $this->makeUser(id: 5);
        $config = $this->makeConfig($user);

        $entry1 = $this->makeEntry(id: 100);
        $entry2 = $this->makeEntry(id: 200);

        $this->entryRepository->method('findOneByUserAndId')->willReturnCallback(
            static fn (object $u, int $id): ?VaultEntry => match ($id) {
                100 => $entry1,
                200 => $entry2,
                default => null,
            },
        );

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->changeMasterPassword($config, 'new-salt-1234567', [
            ['id' => 100, 'encryptedData' => 'new-cipher-1', 'iv' => 'iv-1'],
            ['id' => 200, 'encryptedData' => 'new-cipher-2', 'iv' => 'iv-2'],
        ]);

        self::assertSame('new-salt-1234567', $config->getArgon2Salt());
        self::assertSame('new-cipher-1', $entry1->getEncryptedData());
        self::assertSame('iv-1', $entry1->getIv());
        self::assertSame('new-cipher-2', $entry2->getEncryptedData());
        self::assertSame('iv-2', $entry2->getIv());
    }

    public function testChangeMasterPasswordSkipsEntriesNotOwnedByUser(): void
    {
        // Defense in depth: if the front somehow ships an id pointing to
        // another user's entry, the user-scoped lookup returns null and
        // we silently skip — the entry must NOT be re-encrypted with the
        // wrong key (which would lock the rightful owner out).
        $user = $this->makeUser();
        $config = $this->makeConfig($user);

        $owned = $this->makeEntry(id: 100, encryptedData: 'original');

        $this->entryRepository->method('findOneByUserAndId')->willReturnCallback(
            static fn (object $u, int $id): ?VaultEntry => 100 === $id ? $owned : null,
        );

        $this->manager->changeMasterPassword($config, 'new-salt-1234567', [
            ['id' => 100, 'encryptedData' => 'new', 'iv' => 'iv'],
            ['id' => 999, 'encryptedData' => 'attack', 'iv' => 'evil-iv'],
        ]);

        self::assertSame('new', $owned->getEncryptedData());
        // No exception on the unknown id, just silently skipped.
    }

    public function testDestroyVaultDeletesEntriesFoldersAndConfig(): void
    {
        // Nuke-the-vault flow: emergency reset when user forgot master
        // password. Must DELETE all entries + folders + config row (in
        // that order — entries first to avoid FK violations on folder
        // delete).
        $user = $this->makeUser(id: 7);
        $config = $this->makeConfig($user);

        // 2 DELETE queries (entries + folders) — both must execute.
        $query = $this->createMock(Query::class);
        $query->method('setParameter')->willReturnSelf();
        $query->expects(self::exactly(2))->method('execute');

        $this->entityManager->expects(self::exactly(2))
            ->method('createQuery')
            ->willReturn($query);

        $this->entityManager->expects(self::once())->method('remove')->with($config);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->destroyVault($user, $config);
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeConfig(User $user): VaultUserConfig
    {
        $config = new VaultUserConfig();
        (new ReflectionProperty(VaultUserConfig::class, 'id'))->setValue($config, 1);
        $config->setUser($user);
        $config->setArgon2Salt('old-salt-12345678');

        return $config;
    }

    private function makeEntry(int $id, string $encryptedData = 'cipher'): VaultEntry
    {
        $entry = new VaultEntry();
        (new ReflectionProperty(VaultEntry::class, 'id'))->setValue($entry, $id);
        $entry->setUser(new User());
        $entry->setType(VaultRecordTypeEnum::Login);
        $entry->setTitle('e');
        $entry->setEncryptedData($encryptedData);
        $entry->setIv('iv-old');

        return $entry;
    }
}
