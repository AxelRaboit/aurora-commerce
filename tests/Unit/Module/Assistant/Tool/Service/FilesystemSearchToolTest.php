<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\Tool\Service;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Service\FilesystemSearchTool;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemSearchToolTest extends TestCase
{
    private string $tmpRoot;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir().'/aurora-fssearch-'.bin2hex(random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->filesystem->remove($this->tmpRoot);
        }
    }

    public function testMatchesByFilename(): void
    {
        file_put_contents($this->tmpRoot.'/Commercial-invoice.txt', 'unrelated content');
        file_put_contents($this->tmpRoot.'/other.txt', 'whatever');

        $result = $this->makeTool()->execute(['query' => 'invoice', 'mode' => 'name'], $this->makeUser());

        self::assertStringContainsString('Commercial-invoice.txt', $result);
        self::assertStringNotContainsString('other.txt', $result);
    }

    public function testMatchesByContent(): void
    {
        file_put_contents($this->tmpRoot.'/notes.txt', "line 1\nthe Commercial invoice is here\nline 3");
        file_put_contents($this->tmpRoot.'/random.txt', 'irrelevant');

        $result = $this->makeTool()->execute(['query' => 'Commercial invoice', 'mode' => 'content'], $this->makeUser());

        self::assertStringContainsString('notes.txt:2', $result);
        self::assertStringContainsString('Commercial invoice', $result);
    }

    public function testSkipsHiddenAndNoiseDirs(): void
    {
        mkdir($this->tmpRoot.'/.git');
        file_put_contents($this->tmpRoot.'/.git/secret-invoice.txt', 'shadow');
        mkdir($this->tmpRoot.'/node_modules');
        file_put_contents($this->tmpRoot.'/node_modules/vendor-invoice.txt', 'shadow');
        file_put_contents($this->tmpRoot.'/visible-invoice.txt', 'ok');

        $result = $this->makeTool()->execute(['query' => 'invoice', 'mode' => 'name'], $this->makeUser());

        self::assertStringContainsString('visible-invoice.txt', $result);
        self::assertStringNotContainsString('secret-invoice.txt', $result);
        self::assertStringNotContainsString('vendor-invoice.txt', $result);
    }

    public function testSkipsBinaryFilesForContentMatch(): void
    {
        file_put_contents($this->tmpRoot.'/binary.bin', "before\0invoice\0after");
        file_put_contents($this->tmpRoot.'/text.txt', 'invoice here');

        $result = $this->makeTool()->execute(['query' => 'invoice', 'mode' => 'content'], $this->makeUser());

        self::assertStringContainsString('text.txt', $result);
        self::assertStringNotContainsString('binary.bin', $result);
    }

    public function testEmptyQueryReturnsError(): void
    {
        $result = $this->makeTool()->execute(['query' => '   '], $this->makeUser());

        self::assertStringStartsWith('Error: missing or empty "query"', $result);
    }

    public function testNoActiveMountPointsReturnsError(): void
    {
        $repository = $this->createStub(AssistantMountPointRepository::class);
        $repository->method('findActiveForUser')->willReturn([]);
        $tool = new FilesystemSearchTool(new MountPointPathGuard($repository));

        $result = $tool->execute(['query' => 'anything'], $this->makeUser());

        self::assertStringContainsString('no active mount points', $result);
    }

    public function testScopedPathOutsideMountIsRejected(): void
    {
        $outside = sys_get_temp_dir().'/aurora-outside-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($outside);

        try {
            $result = $this->makeTool()->execute(
                ['query' => 'x', 'path' => $outside],
                $this->makeUser(),
            );
            self::assertStringStartsWith('Error: path is outside', $result);
        } finally {
            $this->filesystem->remove($outside);
        }
    }

    private function makeTool(): FilesystemSearchTool
    {
        $mountPoint = new AssistantMountPoint();
        $mountPoint->setName('test');
        $mountPoint->setPath($this->tmpRoot);
        $mountPoint->setAccess(MountPointAccessEnum::ReadOnly);
        $mountPoint->setActive(true);

        $repository = $this->createStub(AssistantMountPointRepository::class);
        $repository->method('findActiveForUser')->willReturn([$mountPoint]);

        return new FilesystemSearchTool(new MountPointPathGuard($repository));
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
