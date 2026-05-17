<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryItemTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new GalleryItem())->getId());
    }

    public function testReferenceIsNullByDefault(): void
    {
        self::assertNull((new GalleryItem())->getReference());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $item = new GalleryItem();

        $item->setReference('REF-001');
        self::assertSame('REF-001', $item->getReference());

        $item->setReference(null);
        self::assertNull($item->getReference());
    }

    public function testPositionAndNumberDefaultToZero(): void
    {
        $item = new GalleryItem();

        self::assertSame(0, $item->getPosition());
        self::assertSame(0, $item->getNumber());
    }

    public function testPositionGetterAndSetter(): void
    {
        $item = (new GalleryItem())->setPosition(5);

        self::assertSame(5, $item->getPosition());
    }

    public function testNumberGetterAndSetter(): void
    {
        $item = (new GalleryItem())->setNumber(12);

        self::assertSame(12, $item->getNumber());
    }

    public function testTakenAtIsNullByDefault(): void
    {
        self::assertNull((new GalleryItem())->getTakenAt());
    }

    public function testTakenAtGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2024-06-01');
        $item = (new GalleryItem())->setTakenAt($date);

        self::assertSame($date, $item->getTakenAt());

        $item->setTakenAt(null);
        self::assertNull($item->getTakenAt());
    }

    public function testCaptionIsNullByDefault(): void
    {
        self::assertNull((new GalleryItem())->getCaption());
    }

    public function testCaptionGetterAndSetter(): void
    {
        $item = (new GalleryItem())->setCaption('A sunset.');

        self::assertSame('A sunset.', $item->getCaption());

        $item->setCaption(null);
        self::assertNull($item->getCaption());
    }

    public function testGalleryGetterAndSetter(): void
    {
        $gallery = new Gallery();
        $item = (new GalleryItem())->setGallery($gallery);

        self::assertSame($gallery, $item->getGallery());
    }

    public function testMediaGetterAndSetter(): void
    {
        $media = $this->createStub(MediaInterface::class);
        $item = (new GalleryItem())->setMedia($media);

        self::assertSame($media, $item->getMedia());
    }

    public function testSettersReturnSelf(): void
    {
        $item = new GalleryItem();
        $media = $this->createStub(MediaInterface::class);

        self::assertSame($item, $item->setReference('r'));
        self::assertSame($item, $item->setGallery(new Gallery()));
        self::assertSame($item, $item->setMedia($media));
        self::assertSame($item, $item->setPosition(1));
        self::assertSame($item, $item->setNumber(1));
        self::assertSame($item, $item->setTakenAt(new DateTimeImmutable()));
        self::assertSame($item, $item->setCaption('c'));
    }
}
