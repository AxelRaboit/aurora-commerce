<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Manager\GalleryItemManager;
use Aurora\Module\Photo\Gallery\Repository\GalleryItemRepository;
use Aurora\Module\Photo\Gallery\Service\ExifReader;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class GalleryItemManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private GalleryItemRepository $itemRepository;
    private MediaRepository $mediaRepository;
    private ExifReader $exifReader;
    private GalleryItemManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->itemRepository = $this->createStub(GalleryItemRepository::class);
        $this->mediaRepository = $this->createStub(MediaRepository::class);
        // ExifReader is final readonly so we use a real instance pointed at a
        // non-existent directory — readDateTimeOriginal then returns null,
        // which is what most tests want.
        $this->exifReader = new ExifReader('/nonexistent/uploads');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger($this->em, $security);

        $this->manager = new GalleryItemManager(
            $this->em,
            $this->itemRepository,
            $this->mediaRepository,
            $auditLogger,
            $this->exifReader,
        );
    }

    private function makeGallery(int $id = 1): Gallery
    {
        $gallery = new Gallery();
        (new ReflectionProperty(Gallery::class, 'id'))->setValue($gallery, $id);

        return $gallery;
    }

    private function makeMedia(int $id): Media
    {
        $media = (new Media())->setOriginalName('m.jpg')->setPath('m.jpg');
        (new ReflectionProperty(Media::class, 'id'))->setValue($media, $id);

        return $media;
    }

    public function testAddItemsReturnsZeroOnEmptyList(): void
    {
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        self::assertSame(0, $this->manager->addItems($this->makeGallery(), []));
    }

    public function testAddItemsFiltersZeroAndNegativeIds(): void
    {
        $this->em->expects(self::never())->method('persist');

        self::assertSame(0, $this->manager->addItems($this->makeGallery(), [0, -3]));
    }

    public function testAddItemsAttachesNewMedia(): void
    {
        $gallery = $this->makeGallery(42);
        $this->itemRepository->method('nextPositionForGallery')->willReturn(5);
        $this->mediaRepository->method('find')->willReturnCallback(
            fn (int $id): Media => $this->makeMedia($id),
        );

        $persisted = [];
        // Two GalleryItem persists + one AuditLog persist.
        $this->em->expects(self::exactly(3))->method('persist')->willReturnCallback(
            function (object $entity) use (&$persisted): void { $persisted[] = $entity; },
        );

        $added = $this->manager->addItems($gallery, [10, 20]);

        self::assertSame(2, $added);
        self::assertCount(2, $gallery->getItems(), 'items collection should be in sync');
        $items = array_filter($persisted, static fn (object $e): bool => $e instanceof GalleryItem);
        self::assertCount(2, $items);
        $positions = array_map(static fn (GalleryItem $i): int => $i->getPosition(), array_values($items));
        self::assertSame([5, 6], $positions);
    }

    public function testAddItemsSkipsAlreadyAttachedMedia(): void
    {
        $gallery = $this->makeGallery();
        $existing = (new GalleryItem())->setMedia($this->makeMedia(10));
        $gallery->getItems()->add($existing);

        $this->itemRepository->method('nextPositionForGallery')->willReturn(0);
        $this->mediaRepository->method('find')->willReturnCallback(fn (int $id): Media => $this->makeMedia($id));

        $added = $this->manager->addItems($gallery, [10, 20]);

        self::assertSame(1, $added);
    }

    public function testAddItemsSkipsMissingMedia(): void
    {
        $gallery = $this->makeGallery();
        $this->itemRepository->method('nextPositionForGallery')->willReturn(0);
        $this->mediaRepository->method('find')->willReturn(null);

        self::assertSame(0, $this->manager->addItems($gallery, [99]));
    }

    public function testReorderUpdatesPositionsForOwnedItems(): void
    {
        $gallery = $this->makeGallery(7);
        $itemA = (new GalleryItem())->setGallery($gallery)->setPosition(0);
        $itemB = (new GalleryItem())->setGallery($gallery)->setPosition(1);

        $this->itemRepository->method('find')->willReturnCallback(static fn (int $id): ?GalleryItem => match ($id) {
            100 => $itemA,
            200 => $itemB,
            default => null,
        });
        $this->em->expects(self::once())->method('flush');

        $this->manager->reorder($gallery, [200, 100]);

        self::assertSame(0, $itemB->getPosition());
        self::assertSame(1, $itemA->getPosition());
    }

    public function testReorderIgnoresItemsFromOtherGalleries(): void
    {
        $gallery = $this->makeGallery(1);
        $otherGallery = $this->makeGallery(2);
        $alien = (new GalleryItem())->setGallery($otherGallery)->setPosition(99);

        $this->itemRepository->method('find')->willReturn($alien);

        $this->manager->reorder($gallery, [50]);

        self::assertSame(99, $alien->getPosition(), 'alien item must not be touched');
    }

    public function testUpdateCaptionTrimsAndNullsEmpty(): void
    {
        $item = new GalleryItem();
        $item->setCaption('previous');
        $this->em->expects(self::once())->method('flush');

        $this->manager->updateCaption($item, '   ');

        self::assertNull($item->getCaption());
    }

    public function testUpdateCaptionStoresTrimmedValue(): void
    {
        $item = new GalleryItem();

        $this->manager->updateCaption($item, '  Hello  ');

        self::assertSame('Hello', $item->getCaption());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $gallery = $this->makeGallery(1);
        $item = (new GalleryItem())->setGallery($gallery);

        $this->em->expects(self::once())->method('remove')->with($item);
        $this->em->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($item);
    }

    public function testBulkDeleteReturnsCountAndSkipsAliens(): void
    {
        $gallery = $this->makeGallery(1);
        $other = $this->makeGallery(99);
        $own = (new GalleryItem())->setGallery($gallery);
        $alien = (new GalleryItem())->setGallery($other);

        $this->itemRepository->method('find')->willReturnCallback(static fn (int $id): ?GalleryItem => match ($id) {
            10 => $own,
            20 => $alien,
            default => null,
        });

        $this->em->expects(self::once())->method('remove')->with($own);

        self::assertSame(1, $this->manager->bulkDelete($gallery, [10, 20]));
    }

    public function testBulkDeleteEmptyListNoFlush(): void
    {
        $this->em->expects(self::never())->method('flush');

        self::assertSame(0, $this->manager->bulkDelete($this->makeGallery(), []));
    }

    public function testAddItemsAssignsExifTakenAt(): void
    {
        $gallery = $this->makeGallery(7);

        // Real ExifReader pointed at fixture dir containing a JPEG with a
        // known EXIF DateTimeOriginal (2026:01:15 10:00:00).
        $exifReader = new ExifReader(__DIR__.'/fixtures');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger($this->em, $security);

        $manager = new GalleryItemManager(
            $this->em,
            $this->itemRepository,
            $this->mediaRepository,
            $auditLogger,
            $exifReader,
        );

        $this->itemRepository->method('nextPositionForGallery')->willReturn(0);
        $this->itemRepository->method('nextNumberForGallery')->willReturn(1);
        // Media path resolves under fixtures/exif.jpg (relative to upload dir).
        $media = (new Media())->setOriginalName('exif.jpg')->setPath('exif.jpg');
        (new ReflectionProperty(Media::class, 'id'))->setValue($media, 42);
        $this->mediaRepository->method('find')->willReturn($media);

        $manager->addItems($gallery, [42]);

        $items = $gallery->getItems();
        self::assertCount(1, $items);
        $takenAt = $items->first()->getTakenAt();
        self::assertInstanceOf(DateTimeImmutable::class, $takenAt);
        self::assertSame('2026-01-15 10:00:00', $takenAt->format('Y-m-d H:i:s'));
    }

    public function testAddItemsAssignsSequentialNumbers(): void
    {
        $gallery = $this->makeGallery(8);
        $this->itemRepository->method('nextPositionForGallery')->willReturn(0);
        $this->itemRepository->method('nextNumberForGallery')->willReturn(10);
        $this->mediaRepository->method('find')->willReturnCallback(
            fn (int $id): Media => $this->makeMedia($id),
        );

        $this->manager->addItems($gallery, [1, 2, 3]);

        $numbers = [];
        foreach ($gallery->getItems() as $item) {
            $numbers[] = $item->getNumber();
        }
        self::assertSame([10, 11, 12], $numbers);
    }
}
