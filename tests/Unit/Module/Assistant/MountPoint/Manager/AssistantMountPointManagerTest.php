<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\MountPoint\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Assistant\MountPoint\Dto\AssistantMountPointInput;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Manager\AssistantMountPointManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class AssistantMountPointManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private AssistantMountPointManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new AssistantMountPointManager($this->entityManager, $auditLogger);
    }

    public function testCreatePersistsAndAssignsUser(): void
    {
        $user = $this->makeUser();
        $input = new AssistantMountPointInput(
            name: 'Downloads',
            path: '/home/me/Downloads/',
            access: MountPointAccessEnum::ReadOnly,
            active: true,
        );

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $mountPoint = $this->manager->create($user, $input);

        self::assertSame($user, $mountPoint->getUser());
        self::assertSame('Downloads', $mountPoint->getName());
        self::assertSame('/home/me/Downloads', $mountPoint->getPath(), 'trailing slash trimmed');
        self::assertSame(MountPointAccessEnum::ReadOnly, $mountPoint->getAccess());
        self::assertTrue($mountPoint->isActive());
    }

    public function testRootPathIsPreserved(): void
    {
        $input = new AssistantMountPointInput(name: 'root', path: '/');
        $mountPoint = $this->manager->create($this->makeUser(), $input);

        self::assertSame('/', $mountPoint->getPath());
    }

    public function testUpdateAppliesNewInput(): void
    {
        $mountPoint = new AssistantMountPoint();
        $mountPoint->setUser($this->makeUser());
        $mountPoint->setName('old');
        $mountPoint->setPath('/old');
        $mountPoint->setAccess(MountPointAccessEnum::ReadOnly);
        $mountPoint->setActive(true);

        $input = new AssistantMountPointInput(
            name: 'new',
            path: '/new/',
            access: MountPointAccessEnum::ReadWrite,
            active: false,
        );

        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->manager->update($mountPoint, $input);

        self::assertSame('new', $mountPoint->getName());
        self::assertSame('/new', $mountPoint->getPath());
        self::assertSame(MountPointAccessEnum::ReadWrite, $mountPoint->getAccess());
        self::assertFalse($mountPoint->isActive());
    }

    public function testDeleteRemovesEntity(): void
    {
        $mountPoint = new AssistantMountPoint();
        $mountPoint->setUser($this->makeUser());
        $mountPoint->setName('x');
        $mountPoint->setPath('/x');

        $this->entityManager->expects(self::once())->method('remove')->with($mountPoint);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($mountPoint);
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
