<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Media\Library\Enum;

use Aurora\Module\Media\Library\Enum\MimeTypeEnum;
use PHPUnit\Framework\TestCase;

final class MimeTypeEnumTest extends TestCase
{
    public function testIsImageReturnsTrueForAllImageTypes(): void
    {
        self::assertTrue(MimeTypeEnum::Jpeg->isImage());
        self::assertTrue(MimeTypeEnum::Jpg->isImage());
        self::assertTrue(MimeTypeEnum::Png->isImage());
        self::assertTrue(MimeTypeEnum::Gif->isImage());
        self::assertTrue(MimeTypeEnum::Webp->isImage());
        self::assertTrue(MimeTypeEnum::Svg->isImage());
    }

    public function testIsImageReturnsFalseForPdf(): void
    {
        self::assertFalse(MimeTypeEnum::Pdf->isImage());
    }

    public function testIsRasterImageExcludesSvg(): void
    {
        self::assertTrue(MimeTypeEnum::Jpeg->isRasterImage());
        self::assertTrue(MimeTypeEnum::Png->isRasterImage());
        self::assertTrue(MimeTypeEnum::Gif->isRasterImage());
        self::assertTrue(MimeTypeEnum::Webp->isRasterImage());
        self::assertFalse(MimeTypeEnum::Svg->isRasterImage());
        self::assertFalse(MimeTypeEnum::Pdf->isRasterImage());
    }

    public function testIsJpegMatchesBothJpegAndJpg(): void
    {
        self::assertTrue(MimeTypeEnum::Jpeg->isJpeg());
        self::assertTrue(MimeTypeEnum::Jpg->isJpeg());
        self::assertFalse(MimeTypeEnum::Png->isJpeg());
        self::assertFalse(MimeTypeEnum::Pdf->isJpeg());
    }

    public function testSupportsAlphaForPngGifWebp(): void
    {
        self::assertTrue(MimeTypeEnum::Png->supportsAlpha());
        self::assertTrue(MimeTypeEnum::Gif->supportsAlpha());
        self::assertTrue(MimeTypeEnum::Webp->supportsAlpha());
        self::assertFalse(MimeTypeEnum::Jpeg->supportsAlpha());
        self::assertFalse(MimeTypeEnum::Pdf->supportsAlpha());
    }

    public function testSupportsAnimationOnlyForGif(): void
    {
        self::assertTrue(MimeTypeEnum::Gif->supportsAnimation());
        self::assertFalse(MimeTypeEnum::Png->supportsAnimation());
        self::assertFalse(MimeTypeEnum::Webp->supportsAnimation());
    }

    public function testIsRasterMimeTypeEnumStaticReturnsTrueForKnownRaster(): void
    {
        self::assertTrue(MimeTypeEnum::isRasterMimeTypeEnum('image/jpeg'));
        self::assertTrue(MimeTypeEnum::isRasterMimeTypeEnum('image/png'));
    }

    public function testIsRasterMimeTypeEnumStaticReturnsFalseForOthers(): void
    {
        self::assertFalse(MimeTypeEnum::isRasterMimeTypeEnum('image/svg+xml'));
        self::assertFalse(MimeTypeEnum::isRasterMimeTypeEnum('application/pdf'));
        self::assertFalse(MimeTypeEnum::isRasterMimeTypeEnum('unknown/type'));
        self::assertFalse(MimeTypeEnum::isRasterMimeTypeEnum(''));
    }
}
