<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Photo\Gallery\Service\ExifReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class ExifReaderTest extends TestCase
{
    private string $sandbox;
    private Filesystem $filesystem;
    private ExifReader $reader;

    protected function setUp(): void
    {
        $this->sandbox = Path::join(sys_get_temp_dir(), 'aurora-exif-'.uniqid());
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->sandbox);
        $this->reader = new ExifReader($this->sandbox);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->sandbox)) {
            $this->filesystem->remove($this->sandbox);
        }
    }

    public function testReturnsNullForMissingFile(): void
    {
        if (!function_exists('exif_read_data')) {
            self::markTestSkipped('exif extension not available');
        }

        self::assertNull($this->reader->readDateTimeOriginal('does-not-exist.jpg'));
    }

    public function testReturnsNullForFileWithoutExif(): void
    {
        if (!function_exists('exif_read_data')) {
            self::markTestSkipped('exif extension not available');
        }

        $name = 'plain.png';
        $absolute = Path::join($this->sandbox, $name);
        $image = imagecreatetruecolor(20, 20);
        $color = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, 20, 20, $color);
        imagepng($image, $absolute);
        imagedestroy($image);

        self::assertNull($this->reader->readDateTimeOriginal($name));
    }

    public function testReturnsNullWhenExifReadDataUnavailable(): void
    {
        if (function_exists('exif_read_data')) {
            self::markTestSkipped('exif extension is available; cannot exercise the unavailable branch');
        }

        self::assertNull($this->reader->readDateTimeOriginal('anything.jpg'));
    }
}
