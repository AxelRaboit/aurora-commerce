<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\Tool\Service;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Service\FilesystemReadTool;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemReadToolTest extends TestCase
{
    private string $tmpRoot;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir().'/aurora-fsread-'.bin2hex(random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->filesystem->remove($this->tmpRoot);
        }
    }

    public function testReadsFileInsideMountPoint(): void
    {
        file_put_contents($this->tmpRoot.'/hello.txt', 'world');
        $tool = $this->makeTool([$this->tmpRoot]);

        $result = $tool->execute(['path' => $this->tmpRoot.'/hello.txt'], $this->makeUser());

        self::assertStringContainsString('File ', $result);
        self::assertStringContainsString('world', $result);
    }

    public function testRefusesPathOutsideAnyMountPoint(): void
    {
        $other = sys_get_temp_dir().'/aurora-other-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($other);
        file_put_contents($other.'/secret.txt', 'nope');

        try {
            $tool = $this->makeTool([$this->tmpRoot]);
            $result = $tool->execute(['path' => $other.'/secret.txt'], $this->makeUser());
            self::assertStringStartsWith('Error: path is outside', $result);
        } finally {
            $this->filesystem->remove($other);
        }
    }

    public function testRefusesSymlinkEscape(): void
    {
        // Resolved path of the symlink target lies outside the mount point;
        // realpath() collapses it so the guard rejects.
        $outside = sys_get_temp_dir().'/aurora-outside-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($outside);
        file_put_contents($outside.'/leak.txt', 'leak');
        symlink($outside.'/leak.txt', $this->tmpRoot.'/link.txt');

        try {
            $tool = $this->makeTool([$this->tmpRoot]);
            $result = $tool->execute(['path' => $this->tmpRoot.'/link.txt'], $this->makeUser());
            self::assertStringStartsWith('Error: path is outside', $result);
        } finally {
            $this->filesystem->remove($outside);
        }
    }

    public function testListsDirectoryAndSkipsDotfiles(): void
    {
        file_put_contents($this->tmpRoot.'/visible.txt', 'a');
        file_put_contents($this->tmpRoot.'/.hidden', 'b');
        mkdir($this->tmpRoot.'/sub');

        $tool = $this->makeTool([$this->tmpRoot]);
        $result = $tool->execute(['path' => $this->tmpRoot, 'mode' => 'list'], $this->makeUser());

        self::assertStringContainsString('visible.txt', $result);
        self::assertStringContainsString('sub/', $result);
        self::assertStringNotContainsString('.hidden', $result);
    }

    public function testRefusesBinaryFile(): void
    {
        file_put_contents($this->tmpRoot.'/blob.bin', "abc\0def");
        $tool = $this->makeTool([$this->tmpRoot]);

        $result = $tool->execute(['path' => $this->tmpRoot.'/blob.bin'], $this->makeUser());

        self::assertStringContainsString('appears binary', $result);
    }

    public function testReturnsErrorForMissingPath(): void
    {
        $tool = $this->makeTool([$this->tmpRoot]);
        $result = $tool->execute(['path' => $this->tmpRoot.'/nope.txt'], $this->makeUser());

        self::assertStringStartsWith('Error: path does not exist', $result);
    }

    public function testIgnoresInactiveMountPoints(): void
    {
        file_put_contents($this->tmpRoot.'/hello.txt', 'world');
        $tool = $this->makeTool([$this->tmpRoot], active: false);

        $result = $tool->execute(['path' => $this->tmpRoot.'/hello.txt'], $this->makeUser());

        self::assertStringStartsWith('Error: path is outside', $result);
    }

    /** @param list<string> $paths */
    private function makeTool(array $paths, bool $active = true): FilesystemReadTool
    {
        $mountPoints = array_map(static function (string $path): AssistantMountPoint {
            $mountPoint = new AssistantMountPoint();
            $mountPoint->setName('test');
            $mountPoint->setPath($path);
            $mountPoint->setAccess(MountPointAccessEnum::ReadOnly);
            $mountPoint->setActive(true);

            return $mountPoint;
        }, $paths);

        $repository = $this->createStub(AssistantMountPointRepository::class);
        $repository->method('findActiveForUser')->willReturn($active ? $mountPoints : []);

        return new FilesystemReadTool(new MountPointPathGuard($repository));
    }

    private function makeUser(int $id = 42): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
