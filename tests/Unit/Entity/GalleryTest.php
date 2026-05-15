<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryTest extends TestCase
{
    public function testHasPasswordReturnsFalseForNullOrEmptyHash(): void
    {
        $gallery = new Gallery();

        self::assertFalse($gallery->hasPassword());

        $gallery->setPasswordHash('');
        self::assertFalse($gallery->hasPassword());
    }

    public function testHasPasswordReturnsTrueForNonEmptyHash(): void
    {
        $gallery = (new Gallery())->setPasswordHash('$2y$10$abc');

        self::assertTrue($gallery->hasPassword());
    }

    public function testIsExpiredFalseWhenNoExpiry(): void
    {
        self::assertFalse((new Gallery())->isExpired());
    }

    public function testIsExpiredTrueWhenExpiryInPast(): void
    {
        $gallery = (new Gallery())->setExpiresAt(new DateTimeImmutable('-1 day'));

        self::assertTrue($gallery->isExpired());
    }

    public function testIsExpiredFalseWhenExpiryInFuture(): void
    {
        $gallery = (new Gallery())->setExpiresAt(new DateTimeImmutable('+1 day'));

        self::assertFalse($gallery->isExpired());
    }

    public function testHasActiveWatermarkRequiresBothFlagAndText(): void
    {
        $gallery = new Gallery();
        self::assertFalse($gallery->hasActiveWatermark());

        $gallery->setWatermarkEnabled(true);
        self::assertFalse($gallery->hasActiveWatermark(), 'enabled but empty text');

        $gallery->setWatermarkText('© Studio');
        self::assertTrue($gallery->hasActiveWatermark());

        $gallery->setWatermarkText('   ');
        self::assertFalse($gallery->hasActiveWatermark(), 'whitespace-only is empty');

        $gallery->setWatermarkText('© Studio');
        $gallery->setWatermarkEnabled(false);
        self::assertFalse($gallery->hasActiveWatermark(), 'disabled overrides text');
    }

    public function testIsFinalizedReflectsTimestamp(): void
    {
        $gallery = new Gallery();
        self::assertFalse($gallery->isFinalized());

        $gallery->setFinalizedAt(new DateTimeImmutable());
        self::assertTrue($gallery->isFinalized());
    }

    public function testItemsCollectionInitialized(): void
    {
        self::assertCount(0, (new Gallery())->getItems());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $gallery = new Gallery();

        self::assertNull($gallery->getReference());

        $gallery->setReference('REF-2024-001');
        self::assertSame('REF-2024-001', $gallery->getReference());

        $gallery->setReference(null);
        self::assertNull($gallery->getReference());
    }

    public function testDefaultFlags(): void
    {
        $gallery = new Gallery();

        self::assertTrue($gallery->isAllowOriginals());
        self::assertTrue($gallery->isAllowZipDownload());
        self::assertFalse($gallery->isPicksRequireIdentity());
        self::assertFalse($gallery->isWatermarkEnabled());
    }
}
