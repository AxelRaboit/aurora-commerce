<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Service\GalleryWatermarkService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class GalleryWatermarkServiceTest extends TestCase
{
    private GalleryWatermarkService $service;

    protected function setUp(): void
    {
        $this->service = new GalleryWatermarkService(new Filesystem(), Path::join(sys_get_temp_dir(), 'aurora-uploads'));
    }

    private function makeGallery(int $id, ?string $watermarkText): Gallery
    {
        $gallery = new Gallery();
        (new ReflectionProperty(Gallery::class, 'id'))->setValue($gallery, $id);
        if (null !== $watermarkText) {
            $gallery->setWatermarkText($watermarkText);
            $gallery->setWatermarkEnabled(true);
        }

        return $gallery;
    }

    public function testApplyOrPassthroughReturnsSourceWhenWatermarkInactive(): void
    {
        $gallery = $this->makeGallery(1, null);

        $tmp = tempnam(sys_get_temp_dir(), 'wm');
        self::assertNotFalse($tmp);

        $result = $this->service->applyOrPassthrough($gallery, $tmp);

        self::assertSame($tmp, $result);
        unlink($tmp);
    }

    public function testApplyOrPassthroughReturnsSourceWhenFileMissing(): void
    {
        $gallery = $this->makeGallery(1, '© Studio');

        $result = $this->service->applyOrPassthrough($gallery, '/does/not/exist.jpg');

        self::assertSame('/does/not/exist.jpg', $result);
    }

    public function testCachedPathIncludesGalleryIdAndTextHash(): void
    {
        $gallery = $this->makeGallery(42, 'Brand');
        $path = $this->callPrivate('cachedPath', [$gallery, '/tmp/uploads/foo/bar.jpg']);

        $expectedHash = mb_substr(sha1('Brand'), 0, 8);
        self::assertStringContainsString('photo/watermarks/42-'.$expectedHash, $path);
        self::assertStringEndsWith('/bar.jpg', $path);
    }

    public function testDifferentTextProducesDifferentCacheDir(): void
    {
        $gallery1 = $this->makeGallery(1, 'A');
        $gallery2 = $this->makeGallery(1, 'B');

        $p1 = $this->callPrivate('cachedPath', [$gallery1, '/tmp/foo.jpg']);
        $p2 = $this->callPrivate('cachedPath', [$gallery2, '/tmp/foo.jpg']);

        self::assertNotSame($p1, $p2);
    }

    public function testCachedPathShardsByVisitorWatermark(): void
    {
        $gallery = $this->makeGallery(1, 'Brand');

        $anon = $this->callPrivate('cachedPath', [$gallery, '/tmp/foo.jpg', '']);
        $alice = $this->callPrivate('cachedPath', [$gallery, '/tmp/foo.jpg', 'Alice']);
        $bob = $this->callPrivate('cachedPath', [$gallery, '/tmp/foo.jpg', 'Bob']);

        self::assertNotSame($anon, $alice);
        self::assertNotSame($alice, $bob);
        self::assertStringContainsString('/_anon/', $anon);
        self::assertStringEndsWith('/foo.jpg', $alice);
    }

    public function testApplyOrPassthroughKicksInWhenOnlyVisitorWatermarkIsSet(): void
    {
        $gallery = $this->makeGallery(1, null);
        $tmp = tempnam(sys_get_temp_dir(), 'wm');
        self::assertNotFalse($tmp);

        // Without visitor watermark and without gallery watermark → passthrough.
        self::assertSame($tmp, $this->service->applyOrPassthrough($gallery, $tmp, null));
        self::assertSame($tmp, $this->service->applyOrPassthrough($gallery, $tmp, ''));

        // Non-image binary still passthrough (rendering fails gracefully).
        file_put_contents($tmp, 'not-an-image');
        self::assertSame($tmp, $this->service->applyOrPassthrough($gallery, $tmp, 'Alice'));

        unlink($tmp);
    }

    public function testSameTextProducesSameCacheDir(): void
    {
        $gallery1 = $this->makeGallery(1, 'X');
        $gallery2 = $this->makeGallery(1, 'X');

        $p1 = $this->callPrivate('cachedPath', [$gallery1, '/tmp/foo.jpg']);
        $p2 = $this->callPrivate('cachedPath', [$gallery2, '/tmp/foo.jpg']);

        self::assertSame($p1, $p2);
    }

    private function callPrivate(string $method, array $args): mixed
    {
        return (new ReflectionMethod($this->service, $method))->invokeArgs($this->service, $args);
    }
}
