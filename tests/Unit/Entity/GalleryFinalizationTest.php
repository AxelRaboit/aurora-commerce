<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryFinalizationTest extends TestCase
{
    public function testConstructorInitializesFinalizedAt(): void
    {
        $before = new DateTimeImmutable();
        $finalization = new GalleryFinalization();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $finalization->getFinalizedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $finalization->getFinalizedAt()->getTimestamp());
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new GalleryFinalization())->getId());
    }

    public function testGalleryGetterAndSetter(): void
    {
        $gallery = new Gallery();
        $finalization = (new GalleryFinalization())->setGallery($gallery);

        self::assertSame($gallery, $finalization->getGallery());
    }

    public function testVisitorTokenGetterAndSetter(): void
    {
        $finalization = (new GalleryFinalization())->setVisitorToken('abc123');

        self::assertSame('abc123', $finalization->getVisitorToken());
    }

    public function testVisitorNameGetterAndSetter(): void
    {
        $finalization = new GalleryFinalization();

        self::assertNull($finalization->getVisitorName());

        $finalization->setVisitorName('Jane');
        self::assertSame('Jane', $finalization->getVisitorName());

        $finalization->setVisitorName(null);
        self::assertNull($finalization->getVisitorName());
    }

    public function testVisitorEmailGetterAndSetter(): void
    {
        $finalization = new GalleryFinalization();

        self::assertNull($finalization->getVisitorEmail());

        $finalization->setVisitorEmail('jane@example.com');
        self::assertSame('jane@example.com', $finalization->getVisitorEmail());

        $finalization->setVisitorEmail(null);
        self::assertNull($finalization->getVisitorEmail());
    }

    public function testSettersReturnSelf(): void
    {
        $finalization = new GalleryFinalization();

        self::assertSame($finalization, $finalization->setGallery(new Gallery()));
        self::assertSame($finalization, $finalization->setVisitorToken('t'));
        self::assertSame($finalization, $finalization->setVisitorName('n'));
        self::assertSame($finalization, $finalization->setVisitorEmail('e@x.com'));
    }
}
