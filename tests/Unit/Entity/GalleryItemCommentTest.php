<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemComment;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryItemCommentTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new DateTimeImmutable();
        $comment = new GalleryItemComment();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $comment->getCreatedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $comment->getCreatedAt()->getTimestamp());
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new GalleryItemComment())->getId());
    }

    public function testReferenceIsNullByDefault(): void
    {
        self::assertNull((new GalleryItemComment())->getReference());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $comment = new GalleryItemComment();

        $comment->setReference('REF-CMT-001');
        self::assertSame('REF-CMT-001', $comment->getReference());

        $comment->setReference(null);
        self::assertNull($comment->getReference());
    }

    public function testGalleryItemGetterAndSetter(): void
    {
        $galleryItem = new GalleryItem();
        $comment = (new GalleryItemComment())->setGalleryItem($galleryItem);

        self::assertSame($galleryItem, $comment->getGalleryItem());
    }

    public function testVisitorFieldsNullByDefault(): void
    {
        $comment = new GalleryItemComment();

        self::assertNull($comment->getVisitorName());
        self::assertNull($comment->getVisitorEmail());
    }

    public function testVisitorTokenGetterAndSetter(): void
    {
        $comment = (new GalleryItemComment())->setVisitorToken('vt-abc');

        self::assertSame('vt-abc', $comment->getVisitorToken());
    }

    public function testVisitorNameGetterAndSetter(): void
    {
        $comment = (new GalleryItemComment())->setVisitorName('Jane');

        self::assertSame('Jane', $comment->getVisitorName());

        $comment->setVisitorName(null);
        self::assertNull($comment->getVisitorName());
    }

    public function testVisitorEmailGetterAndSetter(): void
    {
        $comment = (new GalleryItemComment())->setVisitorEmail('jane@example.com');

        self::assertSame('jane@example.com', $comment->getVisitorEmail());

        $comment->setVisitorEmail(null);
        self::assertNull($comment->getVisitorEmail());
    }

    public function testContentGetterAndSetter(): void
    {
        $comment = (new GalleryItemComment())->setContent('Great shot!');

        self::assertSame('Great shot!', $comment->getContent());
    }

    public function testSettersReturnSelf(): void
    {
        $comment = new GalleryItemComment();

        self::assertSame($comment, $comment->setReference('r'));
        self::assertSame($comment, $comment->setGalleryItem(new GalleryItem()));
        self::assertSame($comment, $comment->setVisitorToken('vt'));
        self::assertSame($comment, $comment->setVisitorName('n'));
        self::assertSame($comment, $comment->setVisitorEmail('e@x.com'));
        self::assertSame($comment, $comment->setContent('c'));
    }
}
