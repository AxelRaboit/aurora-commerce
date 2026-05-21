<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Media\Library\Service;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MediaUrlGeneratorTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;
    private MediaUrlGenerator $mediaUrlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mediaUrlGenerator = new MediaUrlGenerator($this->urlGenerator);
    }

    private function makeMedia(string $path, array $variants = []): MediaInterface
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getPath')->willReturn($path);
        $media->method('getVariantPath')->willReturnCallback(
            static fn (string $variant): ?string => $variants[$variant] ?? null,
        );

        return $media;
    }

    public function testPublicUrlReturnsNullWhenMediaIsNull(): void
    {
        self::assertNull($this->mediaUrlGenerator->publicUrl(null));
    }

    public function testPublicUrlGeneratesUploadsServeWithRelativePath(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('uploads_serve', ['path' => 'media/photo.jpg'])
            ->willReturn('/uploads/media/photo.jpg');

        $url = $this->mediaUrlGenerator->publicUrl($this->makeMedia('media/photo.jpg'));

        self::assertSame('/uploads/media/photo.jpg', $url);
    }

    public function testPublicUrlAbsoluteReturnsNullWhenMediaIsNull(): void
    {
        self::assertNull($this->mediaUrlGenerator->publicUrlAbsolute(null));
    }

    public function testPublicUrlAbsolutePassesAbsoluteReferenceTypeToTheRouter(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'uploads_serve',
                ['path' => 'media/photo.jpg'],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://aurora.test/uploads/media/photo.jpg');

        $url = $this->mediaUrlGenerator->publicUrlAbsolute($this->makeMedia('media/photo.jpg'));

        self::assertSame('https://aurora.test/uploads/media/photo.jpg', $url);
    }

    public function testVariantUrlReturnsNullWhenMediaIsNull(): void
    {
        self::assertNull($this->mediaUrlGenerator->variantUrl(null, 'medium'));
    }

    public function testVariantUrlReturnsNullWhenVariantPathIsMissing(): void
    {
        // `getVariantPath` returns null when the requested variant wasn't
        // generated for this media (e.g. svg never gets a 'large' bitmap).
        $media = $this->makeMedia('media/icon.svg');

        $this->urlGenerator->expects(self::never())->method('generate');

        self::assertNull($this->mediaUrlGenerator->variantUrl($media, 'large'));
    }

    public function testVariantUrlGeneratesWithTheVariantPath(): void
    {
        $media = $this->makeMedia('media/photo.jpg', [
            'medium' => 'media/photo.medium.jpg',
        ]);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('uploads_serve', ['path' => 'media/photo.medium.jpg'])
            ->willReturn('/uploads/media/photo.medium.jpg');

        $url = $this->mediaUrlGenerator->variantUrl($media, 'medium');

        self::assertSame('/uploads/media/photo.medium.jpg', $url);
    }

    public function testThumbUrlReturnsNullWhenMediaIsNull(): void
    {
        self::assertNull($this->mediaUrlGenerator->thumbUrl(null));
    }

    public function testThumbUrlPrefersMediumWhenAvailable(): void
    {
        $media = $this->makeMedia('media/photo.jpg', [
            'medium' => 'media/photo.medium.jpg',
            'large' => 'media/photo.large.jpg',
        ]);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('uploads_serve', ['path' => 'media/photo.medium.jpg'])
            ->willReturn('/uploads/media/photo.medium.jpg');

        self::assertSame(
            '/uploads/media/photo.medium.jpg',
            $this->mediaUrlGenerator->thumbUrl($media),
        );
    }

    public function testThumbUrlFallsBackToLargeWhenMediumIsMissing(): void
    {
        $media = $this->makeMedia('media/photo.jpg', [
            'large' => 'media/photo.large.jpg',
        ]);

        // First call (medium) returns nothing; second call (large) hits.
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('uploads_serve', ['path' => 'media/photo.large.jpg'])
            ->willReturn('/uploads/media/photo.large.jpg');

        self::assertSame(
            '/uploads/media/photo.large.jpg',
            $this->mediaUrlGenerator->thumbUrl($media),
        );
    }

    public function testThumbUrlFallsBackToOriginalWhenNoVariantExists(): void
    {
        // Pure-SVG / GIF / etc. — no derivative was produced, so the
        // thumb cascade must end on the original public path.
        $media = $this->makeMedia('media/icon.svg');

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('uploads_serve', ['path' => 'media/icon.svg'])
            ->willReturn('/uploads/media/icon.svg');

        self::assertSame(
            '/uploads/media/icon.svg',
            $this->mediaUrlGenerator->thumbUrl($media),
        );
    }
}
