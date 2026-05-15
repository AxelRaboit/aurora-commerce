<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use Aurora\Module\Photo\Gallery\Enum\PickKindEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GalleryPickTest extends TestCase
{
    public function testConstructorInitializesPickedAt(): void
    {
        $before = new DateTimeImmutable();
        $pick = new GalleryPick();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $pick->getPickedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $pick->getPickedAt()->getTimestamp());
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new GalleryPick())->getId());
    }

    public function testDefaultKindIsFavorite(): void
    {
        self::assertSame(PickKindEnum::Favorite, (new GalleryPick())->getKind());
    }

    public function testReferenceIsNullByDefault(): void
    {
        self::assertNull((new GalleryPick())->getReference());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $pick = (new GalleryPick())->setReference('REF-PICK-001');

        self::assertSame('REF-PICK-001', $pick->getReference());

        $pick->setReference(null);
        self::assertNull($pick->getReference());
    }

    public function testGalleryItemGetterAndSetter(): void
    {
        $item = new GalleryItem();
        $pick = (new GalleryPick())->setGalleryItem($item);

        self::assertSame($item, $pick->getGalleryItem());
    }

    public function testVisitorFieldsNullByDefault(): void
    {
        $pick = new GalleryPick();

        self::assertNull($pick->getVisitorName());
        self::assertNull($pick->getVisitorEmail());
    }

    public function testVisitorTokenGetterAndSetter(): void
    {
        $pick = (new GalleryPick())->setVisitorToken('vt-xyz');

        self::assertSame('vt-xyz', $pick->getVisitorToken());
    }

    public function testVisitorNameGetterAndSetter(): void
    {
        $pick = (new GalleryPick())->setVisitorName('Jane');

        self::assertSame('Jane', $pick->getVisitorName());

        $pick->setVisitorName(null);
        self::assertNull($pick->getVisitorName());
    }

    public function testVisitorEmailGetterAndSetter(): void
    {
        $pick = (new GalleryPick())->setVisitorEmail('jane@example.com');

        self::assertSame('jane@example.com', $pick->getVisitorEmail());

        $pick->setVisitorEmail(null);
        self::assertNull($pick->getVisitorEmail());
    }

    public function testKindGetterAndSetter(): void
    {
        $pick = (new GalleryPick())->setKind(PickKindEnum::Print);

        self::assertSame(PickKindEnum::Print, $pick->getKind());
    }

    public function testSettersReturnSelf(): void
    {
        $pick = new GalleryPick();

        self::assertSame($pick, $pick->setReference('r'));
        self::assertSame($pick, $pick->setGalleryItem(new GalleryItem()));
        self::assertSame($pick, $pick->setVisitorToken('vt'));
        self::assertSame($pick, $pick->setVisitorName('n'));
        self::assertSame($pick, $pick->setVisitorEmail('e@x.com'));
        self::assertSame($pick, $pick->setKind(PickKindEnum::Discard));
    }
}
