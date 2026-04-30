<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryInviteTest extends TestCase
{
    public function testConstructorInitializesInvitedAt(): void
    {
        $before = new DateTimeImmutable();
        $invite = new GalleryInvite();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $invite->getInvitedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $invite->getInvitedAt()->getTimestamp());
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new GalleryInvite())->getId());
    }

    public function testSentAtAndLastSeenAtAreNullByDefault(): void
    {
        $invite = new GalleryInvite();

        self::assertNull($invite->getSentAt());
        self::assertNull($invite->getLastSeenAt());
    }

    public function testGalleryGetterAndSetter(): void
    {
        $gallery = new Gallery();
        $invite = (new GalleryInvite())->setGallery($gallery);

        self::assertSame($gallery, $invite->getGallery());
    }

    public function testNameEmailTokenAndVisitorTokenGettersAndSetters(): void
    {
        $invite = (new GalleryInvite())
            ->setName('Jane')
            ->setEmail('jane@example.com')
            ->setToken('tok-abc')
            ->setVisitorToken('vt-xyz');

        self::assertSame('Jane', $invite->getName());
        self::assertSame('jane@example.com', $invite->getEmail());
        self::assertSame('tok-abc', $invite->getToken());
        self::assertSame('vt-xyz', $invite->getVisitorToken());
    }

    public function testMarkSentSetsSentAt(): void
    {
        $invite = new GalleryInvite();
        self::assertNull($invite->getSentAt());

        $before = new DateTimeImmutable();
        $invite->markSent();
        $after = new DateTimeImmutable();

        self::assertNotNull($invite->getSentAt());
        self::assertGreaterThanOrEqual($before->getTimestamp(), $invite->getSentAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $invite->getSentAt()->getTimestamp());
    }

    public function testMarkSeenSetsLastSeenAt(): void
    {
        $invite = new GalleryInvite();
        self::assertNull($invite->getLastSeenAt());

        $before = new DateTimeImmutable();
        $invite->markSeen();
        $after = new DateTimeImmutable();

        self::assertNotNull($invite->getLastSeenAt());
        self::assertGreaterThanOrEqual($before->getTimestamp(), $invite->getLastSeenAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $invite->getLastSeenAt()->getTimestamp());
    }

    public function testSettersReturnSelf(): void
    {
        $invite = new GalleryInvite();

        self::assertSame($invite, $invite->setGallery(new Gallery()));
        self::assertSame($invite, $invite->setName('n'));
        self::assertSame($invite, $invite->setEmail('e@x.com'));
        self::assertSame($invite, $invite->setToken('t'));
        self::assertSame($invite, $invite->setVisitorToken('vt'));
        self::assertSame($invite, $invite->markSent());
        self::assertSame($invite, $invite->markSeen());
    }
}
