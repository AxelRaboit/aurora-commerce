<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\Tool\Service;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Service\FilesystemWriteTool;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemWriteToolTest extends TestCase
{
    private string $tmpRoot;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir().'/aurora-fswrite-'.bin2hex(random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->filesystem->remove($this->tmpRoot);
        }
    }

    public function testWritesNewFileInsideReadWriteMount(): void
    {
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);
        $path = $this->tmpRoot.'/hello.txt';

        $result = $tool->execute(['path' => $path, 'content' => 'world'], $this->makeUser());

        self::assertStringStartsWith('Created ', $result);
        self::assertSame('world', file_get_contents($path));
    }

    public function testUpdatesExistingFile(): void
    {
        file_put_contents($this->tmpRoot.'/note.txt', 'old');
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);

        $result = $tool->execute(['path' => $this->tmpRoot.'/note.txt', 'content' => 'new'], $this->makeUser());

        self::assertStringStartsWith('Updated ', $result);
        self::assertSame('new', file_get_contents($this->tmpRoot.'/note.txt'));
    }

    public function testRefusesWriteOnReadOnlyMount(): void
    {
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadOnly);
        $path = $this->tmpRoot.'/nope.txt';

        $result = $tool->execute(['path' => $path, 'content' => 'no'], $this->makeUser());

        self::assertStringStartsWith('Error: path is outside any active ReadWrite mount point', $result);
        self::assertFileDoesNotExist($path);
    }

    public function testRefusesWriteOutsideAnyMount(): void
    {
        $outside = sys_get_temp_dir().'/aurora-outside-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($outside);

        try {
            $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);
            $result = $tool->execute(['path' => $outside.'/leak.txt', 'content' => 'x'], $this->makeUser());
            self::assertStringStartsWith('Error: path is outside', $result);
            self::assertFileDoesNotExist($outside.'/leak.txt');
        } finally {
            $this->filesystem->remove($outside);
        }
    }

    public function testRefusesDirectoryOverwrite(): void
    {
        mkdir($this->tmpRoot.'/sub');
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);

        $result = $tool->execute(['path' => $this->tmpRoot.'/sub', 'content' => 'x'], $this->makeUser());

        self::assertStringContainsString('refusing to overwrite directory', $result);
    }

    public function testRefusesNulPayload(): void
    {
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);

        $result = $tool->execute(['path' => $this->tmpRoot.'/bin.dat', 'content' => "a\0b"], $this->makeUser());

        self::assertStringContainsString('NUL bytes', $result);
        self::assertFileDoesNotExist($this->tmpRoot.'/bin.dat');
    }

    public function testRefusesMissingParentDirectory(): void
    {
        $tool = $this->makeTool(access: MountPointAccessEnum::ReadWrite);

        $result = $tool->execute(['path' => $this->tmpRoot.'/missing/file.txt', 'content' => 'x'], $this->makeUser());

        self::assertStringContainsString('parent directory does not exist', $result);
    }

    private function makeTool(MountPointAccessEnum $access): FilesystemWriteTool
    {
        $mountPoint = new AssistantMountPoint();
        $mountPoint->setName('test');
        $mountPoint->setPath($this->tmpRoot);
        $mountPoint->setAccess($access);
        $mountPoint->setActive(true);

        $repository = $this->createStub(AssistantMountPointRepository::class);
        $repository->method('findActiveForUser')->willReturn([$mountPoint]);

        return new FilesystemWriteTool(new MountPointPathGuard($repository));
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
