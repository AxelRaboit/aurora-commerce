<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Serializer;

use Aurora\Module\Media\Library\Entity\Media;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemComment;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use Aurora\Module\Photo\Gallery\Enum\PickKindEnum;
use Aurora\Module\Photo\Gallery\Repository\GalleryFinalizationRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryInviteRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryItemCommentRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryPickRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryRepository;
use Aurora\Module\Photo\Gallery\Serializer\GallerySerializer;
use Aurora\Tests\Concern\CreatesStorageUrlGenerators;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class GallerySerializerTest extends TestCase
{
    use CreatesStorageUrlGenerators;

    private GalleryPickRepository $pickRepository;
    private GalleryItemCommentRepository $commentRepository;
    private GalleryFinalizationRepository $finalizationRepository;
    private GalleryInviteRepository $inviteRepository;
    private GalleryRepository $galleryRepository;
    private GallerySerializer $serializer;

    protected function setUp(): void
    {
        $this->pickRepository = $this->createStub(GalleryPickRepository::class);
        $this->commentRepository = $this->createStub(GalleryItemCommentRepository::class);
        $this->commentRepository->method('findAllForGallery')->willReturn([]);
        $this->finalizationRepository = $this->createStub(GalleryFinalizationRepository::class);
        $this->finalizationRepository->method('findAllForGallery')->willReturn([]);
        $this->finalizationRepository->method('countForGallery')->willReturn(0);
        $this->inviteRepository = $this->createStub(GalleryInviteRepository::class);
        $this->inviteRepository->method('findAllForGallery')->willReturn([]);
        $this->galleryRepository = $this->createStub(GalleryRepository::class);
        $this->galleryRepository->method('countItemsByGalleries')->willReturn([]);
        $this->serializer = new GallerySerializer(
            $this->pickRepository,
            $this->commentRepository,
            $this->finalizationRepository,
            $this->inviteRepository,
            $this->galleryRepository,
            $this->makeMediaUrlGenerator(),
        );
    }

    private static function setId(object $entity, int $id): void
    {
        (new ReflectionProperty($entity::class, 'id'))->setValue($entity, $id);
    }

    private function makeGallery(int $id = 1): Gallery
    {
        $gallery = (new Gallery())
            ->setTitle('Wedding')
            ->setSlug('wedding')
            ->setCreatedBy(new User());
        self::setId($gallery, $id);
        $gallery->setCreatedAtValue(); // init createdAt + updatedAt

        return $gallery;
    }

    private function makeMedia(int $id = 100, ?string $alt = null): Media
    {
        $media = (new Media())
            ->setOriginalName('photo.jpg')
            ->setPath('2026/04/photo.jpg')
            ->setVariants(['medium' => '2026/04/photo-medium.jpg'])
            ->setAlt($alt);
        self::setId($media, $id);

        return $media;
    }

    public function testSerializeMinimalShape(): void
    {
        $gallery = $this->makeGallery(42);

        $payload = $this->serializer->serialize($gallery);

        self::assertSame(42, $payload['id']);
        self::assertSame('wedding', $payload['slug']);
        self::assertSame('Wedding', $payload['title']);
        self::assertNull($payload['description']);
        self::assertFalse($payload['hasPassword']);
        self::assertNull($payload['coverMediaId']);
        self::assertNull($payload['coverMediaUrl']);
        self::assertNull($payload['expiresAt']);
        self::assertTrue($payload['allowOriginals']);
        self::assertTrue($payload['allowZipDownload']);
        self::assertFalse($payload['picksRequireIdentity']);
        self::assertFalse($payload['watermarkEnabled']);
        self::assertNull($payload['watermarkText']);
        self::assertNull($payload['client']);
        self::assertNull($payload['finalizedAt']);
        self::assertSame(0, $payload['itemCount']);
        self::assertSame(0, $payload['finalizationCount']);
        self::assertIsString($payload['createdAt']);
        self::assertIsString($payload['updatedAt']);
    }

    public function testSerializeWithPasswordAndExpiry(): void
    {
        $expires = new DateTimeImmutable('2026-12-31T00:00:00+00:00');
        $gallery = $this->makeGallery();
        $gallery->setPasswordHash('$2y$10$x');
        $gallery->setExpiresAt($expires);

        $payload = $this->serializer->serialize($gallery);

        self::assertTrue($payload['hasPassword']);
        self::assertSame($expires->format(DateTimeInterface::ATOM), $payload['expiresAt']);
    }

    public function testSerializeWithCoverMediaUsesMediumVariant(): void
    {
        $gallery = $this->makeGallery();
        $cover = $this->makeMedia(99);
        $gallery->setCoverMedia($cover);

        $payload = $this->serializer->serialize($gallery);

        self::assertSame(99, $payload['coverMediaId']);
        self::assertSame('/uploads/2026/04/photo-medium.jpg', $payload['coverMediaUrl']);
    }

    public function testSerializeFallsBackToPublicUrlWhenNoVariant(): void
    {
        $gallery = $this->makeGallery();
        $cover = (new Media())->setOriginalName('p.jpg')->setPath('raw.jpg');
        self::setId($cover, 7);
        $gallery->setCoverMedia($cover);

        $payload = $this->serializer->serialize($gallery);

        self::assertSame('/uploads/raw.jpg', $payload['coverMediaUrl']);
    }

    public function testSerializeWithClientContact(): void
    {
        $gallery = $this->makeGallery();
        $contact = new Contact();
        $contact->setFirstName('Camille')->setLastName('Doe')->setEmail('c@x.com');
        self::setId($contact, 12);
        $gallery->setClientContact($contact);

        $payload = $this->serializer->serialize($gallery);

        self::assertSame(['id' => 12, 'name' => $contact->getFullName(), 'email' => 'c@x.com'], $payload['client']);
    }

    public function testSerializeFinalized(): void
    {
        $gallery = $this->makeGallery();
        $gallery->setFinalizedAt(new DateTimeImmutable('2026-04-30T12:00:00+00:00'));
        $gallery->setFinalizedByName('Bob');
        $gallery->setFinalizedByEmail('bob@x.com');

        $payload = $this->serializer->serialize($gallery);

        self::assertSame('2026-04-30T12:00:00+00:00', $payload['finalizedAt']);
        self::assertSame('Bob', $payload['finalizedByName']);
        self::assertSame('bob@x.com', $payload['finalizedByEmail']);
    }

    public function testSerializeItems(): void
    {
        $gallery = $this->makeGallery();
        $media = $this->makeMedia(50, 'A bride');
        $taken = new DateTimeImmutable('2026-01-15T10:00:00+00:00');
        $item = (new GalleryItem())
            ->setGallery($gallery)
            ->setMedia($media)
            ->setPosition(3)
            ->setNumber(7)
            ->setTakenAt($taken)
            ->setCaption('Kiss');
        self::setId($item, 200);
        $gallery->getItems()->add($item);

        $items = $this->serializer->serializeItems($gallery);

        self::assertCount(1, $items);
        self::assertSame([
            'id' => 200,
            'mediaId' => 50,
            'thumb' => '/uploads/2026/04/photo-medium.jpg',
            'medium' => '/uploads/2026/04/photo-medium.jpg',
            'full' => '/uploads/2026/04/photo.jpg',
            'caption' => 'Kiss',
            'alt' => 'A bride',
            'position' => 3,
            'number' => 7,
            'takenAt' => $taken->format(DateTimeInterface::ATOM),
        ], $items[0]);
    }

    public function testSerializeItemsThumbFallsBackToLargeThenPublic(): void
    {
        $gallery = $this->makeGallery();
        $largeOnly = (new Media())->setOriginalName('m.jpg')->setPath('m.jpg')
            ->setVariants(['large' => 'large/m.jpg']);
        self::setId($largeOnly, 11);
        $rawOnly = (new Media())->setOriginalName('r.jpg')->setPath('r.jpg');
        self::setId($rawOnly, 12);

        $i1 = (new GalleryItem())->setGallery($gallery)->setMedia($largeOnly)->setPosition(0);
        self::setId($i1, 1);
        $i2 = (new GalleryItem())->setGallery($gallery)->setMedia($rawOnly)->setPosition(1);
        self::setId($i2, 2);
        $gallery->getItems()->add($i1);
        $gallery->getItems()->add($i2);

        $items = $this->serializer->serializeItems($gallery);

        self::assertSame('/uploads/large/m.jpg', $items[0]['thumb']);
        self::assertSame('/uploads/large/m.jpg', $items[0]['full']);
        self::assertSame('/uploads/r.jpg', $items[1]['thumb']);
        self::assertSame('/uploads/r.jpg', $items[1]['medium']);
        self::assertSame('/uploads/r.jpg', $items[1]['full']);
    }

    public function testSerializePickStatsEmpty(): void
    {
        $this->pickRepository->method('findAllForGallery')->willReturn([]);

        $stats = $this->serializer->serializePickStats($this->makeGallery(7));

        self::assertSame([
            'total' => 0,
            'totalsByKind' => [],
            'byItemId' => [],
            'visitorCount' => 0,
            'consensusByItemId' => [],
        ], $stats);
    }

    public function testSerializePickStatsAggregatesByItemAndKind(): void
    {
        $gallery = $this->makeGallery(7);
        $itemA = (new GalleryItem())->setGallery($gallery);
        $itemB = (new GalleryItem())->setGallery($gallery);
        self::setId($itemA, 100);
        self::setId($itemB, 200);

        $picks = [
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('t1'),
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('t2'),
            (new GalleryPick())->setGalleryItem($itemB)->setVisitorToken('t3')
                ->setKind(PickKindEnum::Print),
        ];
        $this->pickRepository->method('findAllForGallery')->willReturn($picks);

        $stats = $this->serializer->serializePickStats($gallery);

        self::assertSame(3, $stats['total']);
        self::assertSame(['favorite' => 2, 'print' => 1], $stats['totalsByKind']);
        self::assertSame([100 => ['favorite' => 2], 200 => ['print' => 1]], $stats['byItemId']);
    }

    public function testSerializePickStatsIncludesConsensus(): void
    {
        $gallery = $this->makeGallery(7);
        $itemA = (new GalleryItem())->setGallery($gallery);
        self::setId($itemA, 100);

        // Same visitor toggles favorite twice (in DB this would be 1 row, but
        // even if duplicated, consensus must dedupe by token).
        $picks = [
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('t1'),
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('t1'),
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('t2'),
        ];
        $this->pickRepository->method('findAllForGallery')->willReturn($picks);

        $stats = $this->serializer->serializePickStats($gallery);

        self::assertSame(2, $stats['visitorCount']);
        self::assertSame([100 => ['favorite' => 2]], $stats['consensusByItemId']);
    }

    public function testSerializeListPayloadEnvelopeShape(): void
    {
        $g1 = $this->makeGallery(1);
        $g2 = $this->makeGallery(2);

        $payload = $this->serializer->serializeListPayload([
            'items' => [$g1, $g2],
            'total' => 5,
            'page' => 2,
            'totalPages' => 3,
        ]);

        self::assertTrue($payload['success']);
        self::assertSame(5, $payload['total']);
        self::assertSame(2, $payload['page']);
        self::assertSame(3, $payload['totalPages']);
        self::assertCount(2, $payload['items']);
        self::assertSame(1, $payload['items'][0]['id']);
        self::assertSame(2, $payload['items'][1]['id']);
    }

    public function testSerializeFinalizationsBasic(): void
    {
        $gallery = $this->makeGallery(1);
        $itemA = (new GalleryItem())->setGallery($gallery);
        self::setId($itemA, 50);
        $itemB = (new GalleryItem())->setGallery($gallery);
        self::setId($itemB, 51);

        $finalization = (new GalleryFinalization())
            ->setGallery($gallery)
            ->setVisitorToken('tok1')
            ->setVisitorName('Alice')
            ->setVisitorEmail('a@x.com');
        self::setId($finalization, 7);

        $picks = [
            (new GalleryPick())->setGalleryItem($itemA)->setVisitorToken('tok1'),
            (new GalleryPick())->setGalleryItem($itemB)->setVisitorToken('tok1')
                ->setKind(PickKindEnum::Print),
        ];

        $finalizations = $this->createStub(GalleryFinalizationRepository::class);
        $finalizations->method('findAllForGallery')->willReturn([$finalization]);
        $finalizations->method('countForGallery')->willReturn(1);
        $this->pickRepository->method('findAllForGallery')->willReturn($picks);
        $invites = $this->createStub(GalleryInviteRepository::class);
        $invites->method('findAllForGallery')->willReturn([]);

        $serializer = new GallerySerializer($this->pickRepository, $this->commentRepository, $finalizations, $invites, $this->galleryRepository, $this->makeMediaUrlGenerator());

        $rows = $serializer->serializeFinalizations($gallery);

        self::assertCount(1, $rows);
        self::assertSame(7, $rows[0]['id']);
        self::assertSame('tok1', $rows[0]['visitorToken']);
        self::assertSame('Alice', $rows[0]['visitorName']);
        self::assertSame('a@x.com', $rows[0]['visitorEmail']);
        self::assertIsString($rows[0]['finalizedAt']);
        self::assertNull($rows[0]['invitedAs']);
        self::assertSame([50], $rows[0]['picksByKind']['favorite']);
        self::assertSame([51], $rows[0]['picksByKind']['print']);
        self::assertSame([], $rows[0]['picksByKind']['discard']);
    }

    public function testSerializeFinalizationsFlagsInviteIdentityMismatch(): void
    {
        $gallery = $this->makeGallery(1);

        $finalization = (new GalleryFinalization())
            ->setGallery($gallery)
            ->setVisitorToken('tok1')
            ->setVisitorName('Edited')
            ->setVisitorEmail('edited@x.com');
        self::setId($finalization, 1);

        $invite = (new GalleryInvite())
            ->setName('Original')
            ->setEmail('orig@x.com')
            ->setToken('inv-tok')
            ->setVisitorToken('tok1');
        self::setId($invite, 1);

        $finalizations = $this->createStub(GalleryFinalizationRepository::class);
        $finalizations->method('findAllForGallery')->willReturn([$finalization]);
        $finalizations->method('countForGallery')->willReturn(1);
        $this->pickRepository->method('findAllForGallery')->willReturn([]);
        $invites = $this->createStub(GalleryInviteRepository::class);
        $invites->method('findAllForGallery')->willReturn([$invite]);

        $serializer = new GallerySerializer($this->pickRepository, $this->commentRepository, $finalizations, $invites, $this->galleryRepository, $this->makeMediaUrlGenerator());

        $rows = $serializer->serializeFinalizations($gallery);

        self::assertSame(['name' => 'Original', 'email' => 'orig@x.com'], $rows[0]['invitedAs']);
    }

    public function testSerializeInvitesIncludesFinalizedAt(): void
    {
        $gallery = $this->makeGallery(1);

        $invite = (new GalleryInvite())
            ->setName('Inv')
            ->setEmail('inv@x.com')
            ->setToken('inv-tok')
            ->setVisitorToken('tok1');
        self::setId($invite, 9);

        $finalization = (new GalleryFinalization())
            ->setGallery($gallery)
            ->setVisitorToken('tok1');

        $invites = $this->createStub(GalleryInviteRepository::class);
        $invites->method('findAllForGallery')->willReturn([$invite]);
        $finalizations = $this->createStub(GalleryFinalizationRepository::class);
        $finalizations->method('findAllForGallery')->willReturn([$finalization]);
        $finalizations->method('countForGallery')->willReturn(1);

        $serializer = new GallerySerializer($this->pickRepository, $this->commentRepository, $finalizations, $invites, $this->galleryRepository, $this->makeMediaUrlGenerator());

        $rows = $serializer->serializeInvites($gallery);

        self::assertCount(1, $rows);
        self::assertSame(9, $rows[0]['id']);
        self::assertSame('Inv', $rows[0]['name']);
        self::assertSame('inv@x.com', $rows[0]['email']);
        self::assertIsString($rows[0]['finalizedAt']);
        self::assertNull($rows[0]['sentAt']);
        self::assertNull($rows[0]['lastSeenAt']);
    }

    public function testSerializeCommentShape(): void
    {
        $gallery = $this->makeGallery(1);
        $item = (new GalleryItem())->setGallery($gallery);
        self::setId($item, 77);

        $comment = (new GalleryItemComment())
            ->setGalleryItem($item)
            ->setContent('Beautiful!')
            ->setVisitorToken('tok')
            ->setVisitorName('Vi')
            ->setVisitorEmail('v@x.com');
        self::setId($comment, 5);

        $payload = $this->serializer->serializeComment($comment);

        self::assertSame(5, $payload['id']);
        self::assertSame(77, $payload['itemId']);
        self::assertSame('Beautiful!', $payload['content']);
        self::assertSame('Vi', $payload['visitorName']);
        self::assertSame('v@x.com', $payload['visitorEmail']);
        self::assertIsString($payload['createdAt']);
    }

    public function testSerializeCommentsForGallery(): void
    {
        $gallery = $this->makeGallery(1);
        $item = (new GalleryItem())->setGallery($gallery);
        self::setId($item, 77);

        $first = (new GalleryItemComment())
            ->setGalleryItem($item)
            ->setContent('First comment')
            ->setVisitorToken('tok-1')
            ->setVisitorName('Alice');
        self::setId($first, 10);

        $second = (new GalleryItemComment())
            ->setGalleryItem($item)
            ->setContent('Second comment')
            ->setVisitorToken('tok-2')
            ->setVisitorName('Bob');
        self::setId($second, 11);

        $commentRepository = $this->createStub(GalleryItemCommentRepository::class);
        $commentRepository->method('findAllForGallery')->willReturn([$first, $second]);

        $serializer = new GallerySerializer(
            $this->pickRepository,
            $commentRepository,
            $this->finalizationRepository,
            $this->inviteRepository,
            $this->galleryRepository,
            $this->makeMediaUrlGenerator(),
        );

        $payload = $serializer->serializeComments($gallery);

        self::assertCount(2, $payload);
        self::assertSame(10, $payload[0]['id']);
        self::assertSame('First comment', $payload[0]['content']);
        self::assertSame(11, $payload[1]['id']);
        self::assertSame('Second comment', $payload[1]['content']);
    }
}
