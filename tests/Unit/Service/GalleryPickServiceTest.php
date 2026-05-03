<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use Aurora\Module\Photo\Gallery\Enum\PickKindEnum;
use Aurora\Module\Photo\Gallery\Exception\MaxPicksReachedException;
use Aurora\Module\Photo\Gallery\Repository\GalleryFinalizationRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryInviteRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryPickRepository;
use Aurora\Module\Photo\Gallery\Service\GalleryPickService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class GalleryPickServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private GalleryPickRepository $pickRepository;
    private GalleryFinalizationRepository $finalizationRepository;
    private GalleryInviteRepository $inviteRepository;
    private GalleryPickService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->pickRepository = $this->createStub(GalleryPickRepository::class);
        $this->finalizationRepository = $this->createStub(GalleryFinalizationRepository::class);
        $this->inviteRepository = $this->createStub(GalleryInviteRepository::class);
        $this->service = new GalleryPickService(
            $this->em,
            $this->pickRepository,
            $this->finalizationRepository,
            $this->inviteRepository,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );
    }

    private function makeGallery(int $id): Gallery
    {
        $gallery = new Gallery();
        (new ReflectionProperty(Gallery::class, 'id'))->setValue($gallery, $id);

        return $gallery;
    }

    private function makeItem(int $id, Gallery $gallery): GalleryItem
    {
        $item = (new GalleryItem())->setGallery($gallery);
        (new ReflectionProperty(GalleryItem::class, 'id'))->setValue($item, $id);

        return $item;
    }

    private function makePick(GalleryItem $item, ?string $name = null, ?string $email = null): GalleryPick
    {
        $pick = new GalleryPick();
        $pick->setGalleryItem($item);
        $pick->setVisitorToken('tok');
        $pick->setVisitorName($name);
        $pick->setVisitorEmail($email);

        return $pick;
    }

    private function makeInvite(string $name, string $email, string $visitorToken = 'tok'): GalleryInvite
    {
        $invite = new GalleryInvite();
        $invite->setName($name);
        $invite->setEmail($email);
        $invite->setToken('inv-token');
        $invite->setVisitorToken($visitorToken);

        return $invite;
    }

    public function testToggleCreatesPickWhenAbsent(): void
    {
        $gallery = $this->makeGallery(1);
        $item = $this->makeItem(10, $gallery);
        $this->pickRepository->method('findOneBy')->willReturn(null);

        $persisted = null;
        $this->em->expects(self::once())->method('persist')
            ->willReturnCallback(function (object $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects(self::atLeastOnce())->method('flush');

        $picked = $this->service->toggle($item, 'tok', 'Bob', 'b@x.com');

        self::assertTrue($picked);
        self::assertInstanceOf(GalleryPick::class, $persisted);
        self::assertSame('Bob', $persisted->getVisitorName());
    }

    public function testToggleRemovesExistingPick(): void
    {
        $gallery = $this->makeGallery(1);
        $item = $this->makeItem(10, $gallery);
        $existing = $this->makePick($item);
        $this->pickRepository->method('findOneBy')->willReturn($existing);

        $this->em->expects(self::once())->method('remove')->with($existing);
        $this->em->expects(self::never())->method('persist');

        self::assertFalse($this->service->toggle($item, 'tok'));
    }

    public function testFinalizePersistsFinalization(): void
    {
        $gallery = $this->makeGallery(1);
        $this->finalizationRepository->method('findOneByVisitor')->willReturn(null);

        $persisted = null;
        $this->em->expects(self::once())->method('persist')
            ->willReturnCallback(function (object $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects(self::atLeastOnce())->method('flush');

        $finalization = $this->service->finalize($gallery, 'tok', 'Alice', 'a@x.com');

        self::assertInstanceOf(GalleryFinalization::class, $persisted);
        self::assertSame($persisted, $finalization);
        self::assertSame('Alice', $finalization->getVisitorName());
        self::assertSame('a@x.com', $finalization->getVisitorEmail());
        self::assertSame('tok', $finalization->getVisitorToken());
        self::assertSame($gallery, $finalization->getGallery());
    }

    public function testFinalizeIsIdempotent(): void
    {
        $gallery = $this->makeGallery(1);
        $existing = (new GalleryFinalization())
            ->setGallery($gallery)
            ->setVisitorToken('tok')
            ->setVisitorName('B')
            ->setVisitorEmail('b@x.com');
        $this->finalizationRepository->method('findOneByVisitor')->willReturn($existing);

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        self::assertSame($existing, $this->service->finalize($gallery, 'tok', 'B', 'b@x.com'));
    }

    public function testIsFinalizedByReturnsFalseWhenNoFinalization(): void
    {
        $gallery = $this->makeGallery(1);
        $this->finalizationRepository->method('findOneByVisitor')->willReturn(null);

        self::assertFalse($this->service->isFinalizedBy($gallery, 'tok'));
    }

    public function testIsFinalizedByReturnsTrueWhenExists(): void
    {
        $gallery = $this->makeGallery(1);
        $finalization = (new GalleryFinalization())->setGallery($gallery)->setVisitorToken('tok');
        $this->finalizationRepository->method('findOneByVisitor')->willReturn($finalization);

        self::assertTrue($this->service->isFinalizedBy($gallery, 'tok'));
    }

    public function testReopenForRemovesFinalization(): void
    {
        $gallery = $this->makeGallery(1);
        $finalization = (new GalleryFinalization())->setGallery($gallery)->setVisitorToken('tok');
        $this->finalizationRepository->method('findOneByVisitor')->willReturn($finalization);

        $this->em->expects(self::once())->method('remove')->with($finalization);
        $this->em->expects(self::once())->method('flush');

        $this->service->reopenFor($gallery, 'tok');
    }

    public function testReopenForNoOpWhenAbsent(): void
    {
        $gallery = $this->makeGallery(1);
        $this->finalizationRepository->method('findOneByVisitor')->willReturn(null);

        $this->em->expects(self::never())->method('remove');
        $this->em->expects(self::never())->method('flush');

        $this->service->reopenFor($gallery, 'tok');
    }

    public function testVisitorHasIdentityWhenBothProvided(): void
    {
        self::assertTrue($this->service->visitorHasIdentity('tok', 'A', 'a@x.com'));
    }

    public function testVisitorHasIdentityFalsyWhenEmpty(): void
    {
        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->inviteRepository->method('findOneBy')->willReturn(null);

        self::assertFalse($this->service->visitorHasIdentity('tok', '', 'a@x.com'));
        self::assertFalse($this->service->visitorHasIdentity('tok', 'A', ''));
    }

    public function testVisitorHasIdentityFromPriorPick(): void
    {
        $item = $this->makeItem(1, $this->makeGallery(1));
        $this->pickRepository->method('findOneBy')->willReturn($this->makePick($item, 'A', 'a@x.com'));

        self::assertTrue($this->service->visitorHasIdentity('tok', null, null));
    }

    public function testVisitorHasIdentityFalseWhenPriorPickIncomplete(): void
    {
        $item = $this->makeItem(1, $this->makeGallery(1));
        $this->pickRepository->method('findOneBy')->willReturn($this->makePick($item, 'A', null));
        $this->inviteRepository->method('findOneBy')->willReturn(null);

        self::assertFalse($this->service->visitorHasIdentity('tok', null, null));
    }

    public function testVisitorHasIdentityFromInvite(): void
    {
        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->inviteRepository->method('findOneBy')->willReturn($this->makeInvite('Inv', 'i@x.com'));

        self::assertTrue($this->service->visitorHasIdentity('tok', null, null));
    }

    public function testRecoverIdentityKeepsProvidedValues(): void
    {
        [$name, $email] = $this->service->recoverIdentity('tok', 'Given', 'g@x.com');

        self::assertSame(['Given', 'g@x.com'], [$name, $email]);
    }

    public function testRecoverIdentityFillsFromPriorPick(): void
    {
        $item = $this->makeItem(1, $this->makeGallery(1));
        $this->pickRepository->method('findOneBy')->willReturn($this->makePick($item, 'Prior', 'p@x.com'));

        [$name, $email] = $this->service->recoverIdentity('tok', null, null);

        self::assertSame(['Prior', 'p@x.com'], [$name, $email]);
    }

    public function testRecoverIdentityFallsBackToInvite(): void
    {
        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->inviteRepository->method('findOneBy')->willReturn($this->makeInvite('InvName', 'inv@x.com'));

        [$name, $email] = $this->service->recoverIdentity('tok', null, null);

        self::assertSame(['InvName', 'inv@x.com'], [$name, $email]);
    }

    public function testRecoverIdentityReturnsNullsWhenNoHistory(): void
    {
        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->inviteRepository->method('findOneBy')->willReturn(null);

        self::assertSame([null, null], $this->service->recoverIdentity('tok', null, null));
    }

    public function testPicksByVisitorGroupsKindsByItem(): void
    {
        $gallery = $this->makeGallery(1);
        $itemA = $this->makeItem(7, $gallery);
        $itemB = $this->makeItem(8, $gallery);
        $favA = $this->makePick($itemA);
        $printA = (new GalleryPick())
            ->setGalleryItem($itemA)->setVisitorToken('tok')
            ->setKind(PickKindEnum::Print);
        $favB = $this->makePick($itemB);
        $this->pickRepository->method('findByVisitorForGallery')->willReturn([$favA, $printA, $favB]);

        $byItem = $this->service->picksByVisitor($gallery, 'tok');

        self::assertSame(['favorite', 'print'], $byItem[7]);
        self::assertSame(['favorite'], $byItem[8]);
    }

    public function testItemsPickedByFiltersOnKind(): void
    {
        $gallery = $this->makeGallery(1);
        $itemA = $this->makeItem(7, $gallery);
        $itemB = $this->makeItem(8, $gallery);
        $favA = $this->makePick($itemA);
        $printB = (new GalleryPick())
            ->setGalleryItem($itemB)->setVisitorToken('tok')
            ->setKind(PickKindEnum::Print);
        $this->pickRepository->method('findByVisitorForGallery')->willReturn([$favA, $printB]);

        self::assertSame([$itemA], $this->service->itemsPickedBy($gallery, 'tok'));
        self::assertSame([$itemB], $this->service->itemsPickedBy($gallery, 'tok', PickKindEnum::Print));
    }

    public function testFavoriteCountDelegatesToRepository(): void
    {
        $gallery = $this->makeGallery(5);
        $this->pickRepository->method('countForVisitor')->willReturn(7);

        self::assertSame(7, $this->service->favoriteCount($gallery, 'tok'));
    }

    public function testToggleFavoriteRespectsMaxPicks(): void
    {
        $gallery = $this->makeGallery(1);
        $gallery->setMaxPicks(2);
        $item = $this->makeItem(99, $gallery);

        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->pickRepository->method('countForVisitor')->willReturn(2);

        $this->expectException(MaxPicksReachedException::class);
        $this->service->toggle($item, 'tok');
    }

    public function testTogglePrintIgnoresMaxPicks(): void
    {
        $gallery = $this->makeGallery(1);
        $gallery->setMaxPicks(1);
        $item = $this->makeItem(99, $gallery);

        $this->pickRepository->method('findOneBy')->willReturn(null);
        $this->pickRepository->method('countForVisitor')->willReturn(99);

        self::assertTrue($this->service->toggle($item, 'tok', null, null, PickKindEnum::Print));
    }
}
