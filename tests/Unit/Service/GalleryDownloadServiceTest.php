<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Media\Entity\Media;
use Aurora\Module\Photo\Gallery\Service\GalleryDownloadService;
use Aurora\Module\Photo\Gallery\Service\GalleryWatermarkService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class GalleryDownloadServiceTest extends TestCase
{
    private GalleryDownloadService $service;

    protected function setUp(): void
    {
        $this->service = new GalleryDownloadService('/tmp/uploads', new GalleryWatermarkService('/tmp/uploads'));
    }

    public function testNiceNameSanitizesOriginalName(): void
    {
        $media = (new Media())
            ->setOriginalName('Couple at the beach.jpg')
            ->setPath('2026/04/foo.jpg');

        $name = $this->callPrivate('niceName', [$media, 'web']);

        self::assertSame('Couple-at-the-beach-web.jpg', $name);
    }

    public function testNiceNameOriginalVariantHasNoSuffix(): void
    {
        $media = (new Media())
            ->setOriginalName('IMG_001.jpg')
            ->setPath('2026/04/abc.jpg');

        $name = $this->callPrivate('niceName', [$media, 'original']);

        self::assertSame('IMG_001.jpg', $name);
    }

    public function testNiceNameFallsBackToPathFilenameWhenOriginalEmpty(): void
    {
        $media = (new Media())
            ->setOriginalName('')
            ->setPath('2026/04/abc-def.jpg');

        $name = $this->callPrivate('niceName', [$media, 'original']);

        self::assertSame('abc-def.jpg', $name);
    }

    public function testUniqueNameReturnsCandidateIfFree(): void
    {
        $name = $this->callPrivate('uniqueName', ['photo.jpg', []]);

        self::assertSame('photo.jpg', $name);
    }

    public function testUniqueNameAppendsCounterOnCollision(): void
    {
        $taken = ['photo.jpg' => true];

        $name = $this->callPrivate('uniqueName', ['photo.jpg', $taken]);

        self::assertSame('photo-1.jpg', $name);
    }

    public function testUniqueNameSkipsExistingCounters(): void
    {
        $taken = [
            'photo.jpg' => true,
            'photo-1.jpg' => true,
            'photo-2.jpg' => true,
        ];

        $name = $this->callPrivate('uniqueName', ['photo.jpg', $taken]);

        self::assertSame('photo-3.jpg', $name);
    }

    public function testUniqueNameHandlesMissingExtension(): void
    {
        $taken = ['raw' => true];

        $name = $this->callPrivate('uniqueName', ['raw', $taken]);

        self::assertSame('raw-1', $name);
    }

    private function callPrivate(string $method, array $args): mixed
    {
        $reflected = new ReflectionMethod($this->service, $method);

        return $reflected->invokeArgs($this->service, $args);
    }
}
