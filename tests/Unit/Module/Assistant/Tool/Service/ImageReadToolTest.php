<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\Tool\Service;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Service\ImageReadTool;
use Aurora\Module\Assistant\Vision\Contract\VisionDescriberInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class ImageReadToolTest extends TestCase
{
    private string $tmpRoot;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir().'/aurora-imgread-'.bin2hex(random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->filesystem->remove($this->tmpRoot);
        }
    }

    public function testDescribesImageInsideMount(): void
    {
        $path = $this->tmpRoot.'/photo.png';
        file_put_contents($path, 'PNGDATA');

        $describer = $this->createStub(VisionDescriberInterface::class);
        $describer->method('describe')->willReturn('a cat on a keyboard');

        $tool = $this->makeTool($describer);
        $result = $tool->execute(['path' => $path], $this->makeUser());

        self::assertStringContainsString($path, $result);
        self::assertStringContainsString('a cat on a keyboard', $result);
    }

    public function testRejectsUnsupportedExtension(): void
    {
        $path = $this->tmpRoot.'/doc.pdf';
        file_put_contents($path, '%PDF-1.4');

        $tool = $this->makeTool($this->createStub(VisionDescriberInterface::class));
        $result = $tool->execute(['path' => $path], $this->makeUser());

        self::assertStringContainsString('unsupported image extension ".pdf"', $result);
    }

    public function testRejectsImageOutsideMount(): void
    {
        $outside = sys_get_temp_dir().'/aurora-outside-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($outside);
        $path = $outside.'/leak.png';
        file_put_contents($path, 'PNGDATA');

        try {
            $tool = $this->makeTool($this->createStub(VisionDescriberInterface::class));
            $result = $tool->execute(['path' => $path], $this->makeUser());
            self::assertStringStartsWith('Error: image is outside', $result);
        } finally {
            $this->filesystem->remove($outside);
        }
    }

    public function testMissingPathReturnsError(): void
    {
        $tool = $this->makeTool($this->createStub(VisionDescriberInterface::class));
        $result = $tool->execute(['path' => $this->tmpRoot.'/missing.png'], $this->makeUser());

        self::assertStringStartsWith('Error: image does not exist', $result);
    }

    public function testVisionFailureSurfacesAsToolError(): void
    {
        $path = $this->tmpRoot.'/photo.jpg';
        file_put_contents($path, 'JPGDATA');

        $describer = $this->createStub(VisionDescriberInterface::class);
        $describer->method('describe')->willThrowException(new RuntimeException('model timeout'));

        $tool = $this->makeTool($describer);
        $result = $tool->execute(['path' => $path], $this->makeUser());

        self::assertStringContainsString('Vision model failed', $result);
        self::assertStringContainsString('model timeout', $result);
    }

    private function makeTool(VisionDescriberInterface $describer): ImageReadTool
    {
        $mountPoint = new AssistantMountPoint();
        $mountPoint->setName('test');
        $mountPoint->setPath($this->tmpRoot);
        $mountPoint->setAccess(MountPointAccessEnum::ReadOnly);
        $mountPoint->setActive(true);

        $repository = $this->createStub(AssistantMountPointRepository::class);
        $repository->method('findActiveForUser')->willReturn([$mountPoint]);

        return new ImageReadTool(new MountPointPathGuard($repository), $describer);
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
