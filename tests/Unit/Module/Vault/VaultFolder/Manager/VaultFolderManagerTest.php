<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Vault\VaultFolder\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Vault\VaultFolder\Dto\VaultFolderInput;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Vault\VaultFolder\Manager\VaultFolderManager;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class VaultFolderManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private VaultFolderRepository $folderRepository;
    private VaultFolderManager $manager;

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

        $this->manager = new VaultFolderManager($this->entityManager, $auditLogger, $this->folderRepository);
    }

    public function testCreateAssignsUserAndPersists(): void
    {
        $user = $this->makeUser();
        $input = new VaultFolderInput(name: 'Work', color: '#aabbcc');

        $this->folderRepository->method('countNextPositionForParent')->willReturn(0);
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $folder = $this->manager->create($user, $input);

        self::assertSame($user, $folder->getUser());
        self::assertSame('Work', $folder->getName());
        self::assertSame('#aabbcc', $folder->getColor());
        self::assertNull($folder->getParent());
    }

    public function testCreateAppendsToEndOfSiblingListViaRepoCount(): void
    {
        // Position is computed via `countNextPositionForParent`
        // (returns N for "next slot after the N existing siblings"),
        // not from the input. Avoids race conditions when two users
        // create folders simultaneously.
        $user = $this->makeUser();
        $this->folderRepository->expects(self::once())
            ->method('countNextPositionForParent')
            ->with($user, null)
            ->willReturn(5);

        $folder = $this->manager->create($user, new VaultFolderInput(name: 'X', position: 99));

        self::assertSame(5, $folder->getPosition(), 'repo-computed position overrides the input value');
    }

    public function testCreateResolvesParentViaUserScopedLookup(): void
    {
        // Same security guarantee as Vault entries — a user can't make
        // their folder a child of another user's folder.
        $user = $this->makeUser(id: 7);
        $parent = $this->makeFolder(id: 9);

        $this->folderRepository->expects(self::once())
            ->method('findOneByUserAndId')
            ->with($user, 9)
            ->willReturn($parent);
        $this->folderRepository->method('countNextPositionForParent')->willReturn(0);

        $folder = $this->manager->create($user, new VaultFolderInput(name: 'sub', parentId: 9));

        self::assertSame($parent, $folder->getParent());
    }

    public function testCreateLeavesParentNullWhenRepoReturnsNull(): void
    {
        // findOneByUserAndId returns null → folder created at the root
        // silently (the front handles the validation message).
        $user = $this->makeUser();
        $this->folderRepository->method('findOneByUserAndId')->willReturn(null);
        $this->folderRepository->method('countNextPositionForParent')->willReturn(0);

        $folder = $this->manager->create($user, new VaultFolderInput(name: 'X', parentId: 999));

        self::assertNull($folder->getParent());
    }

    public function testUpdateAppliesInputAndFlushes(): void
    {
        $folder = $this->makeFolder(id: 1);
        $folder->setUser($this->makeUser());
        $folder->setName('old');

        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($folder, new VaultFolderInput(name: 'new', color: '#ffeeaa', position: 3));

        self::assertSame('new', $folder->getName());
        self::assertSame('#ffeeaa', $folder->getColor());
        self::assertSame(3, $folder->getPosition());
    }

    public function testUpdateUsesInputPositionDirectly(): void
    {
        // Unlike `create`, `update` does NOT call countNextPositionForParent
        // — the input's position is taken as-is. This makes drag-to-reorder
        // possible from the frontend (the front computes positions and
        // submits a series of updates).
        $folder = $this->makeFolder(id: 1);
        $folder->setUser($this->makeUser());

        $this->folderRepository->expects(self::never())->method('countNextPositionForParent');

        $this->manager->update($folder, new VaultFolderInput(name: 'X', position: 42));

        self::assertSame(42, $folder->getPosition());
    }

    public function testUpdateClearsParentWhenInputHasNoParentId(): void
    {
        $folder = $this->makeFolder(id: 1);
        $folder->setUser($this->makeUser());
        $folder->setParent($this->makeFolder(id: 2));

        $this->manager->update($folder, new VaultFolderInput(name: 'X', parentId: null));

        self::assertNull($folder->getParent());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $folder = $this->makeFolder(id: 1);
        $folder->setUser($this->makeUser());

        $this->entityManager->expects(self::once())->method('remove')->with($folder);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($folder);
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
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
