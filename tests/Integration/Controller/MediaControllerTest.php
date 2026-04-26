<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Media;
use App\Entity\MediaFolder;
use App\Entity\User;
use App\Repository\Media\MediaFolderRepository;
use App\Repository\Media\MediaRepository;
use App\Repository\User\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class MediaControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@velox.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }

    private function postJson(string $url, array $payload): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        return [$this->client->getResponse()->getStatusCode(), json_decode((string) $this->client->getResponse()->getContent(), true) ?? []];
    }

    private function createFolder(string $name, ?int $parentId = null): MediaFolder
    {
        [, $body] = $this->postJson('/admin/media/folders', ['name' => $name, 'parentId' => $parentId]);
        self::assertTrue($body['success']);
        /** @var MediaFolder $folder */
        $folder = static::getContainer()->get(MediaFolderRepository::class)->find($body['folder']['id']);

        return $folder;
    }

    private function createMedia(): Media
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $media = (new Media())
            ->setFilename('dummy.jpg')
            ->setOriginalName('dummy.jpg')
            ->setMimeType('image/jpeg')
            ->setSize(1024)
            ->setPath('dummy.jpg');
        $entityManager->persist($media);
        $entityManager->flush();

        return $media;
    }

    public function testAltIsRequiredOnEdit(): void
    {
        $media = $this->createMedia();

        [$status, $body] = $this->postJson(sprintf('/admin/media/%d/edit', $media->getId()), [
            'alt' => '',
        ]);

        self::assertSame(200, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('alt', $body['errors']);
    }

    public function testEditUpdatesFocalPointAndFolder(): void
    {
        $media = $this->createMedia();
        $folder = $this->createFolder('Banners');

        [$status, $body] = $this->postJson(sprintf('/admin/media/%d/edit', $media->getId()), [
            'alt' => 'A nice banner',
            'caption' => 'Marketing banner for homepage',
            'focalX' => 0.5,
            'focalY' => 0.25,
            'folderId' => $folder->getId(),
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame(0.5, $body['media']['focalX']);
        self::assertSame(0.25, $body['media']['focalY']);
        self::assertSame($folder->getId(), $body['media']['folderId']);
    }

    public function testFolderCycleRejected(): void
    {
        $parent = $this->createFolder('Parent');
        $child = $this->createFolder('Child', $parent->getId());

        [$status, $body] = $this->postJson(sprintf('/admin/media/folders/%d/edit', $parent->getId()), [
            'name' => 'Parent',
            'parentId' => $child->getId(),
        ]);

        self::assertSame(200, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('parentId', $body['errors']);
    }

    public function testFocalPointMustBeInRange(): void
    {
        $media = $this->createMedia();

        [$status, $body] = $this->postJson(sprintf('/admin/media/%d/edit', $media->getId()), [
            'alt' => 'x',
            'focalX' => 1.5,
            'focalY' => -0.2,
        ]);

        self::assertSame(200, $status);
        self::assertFalse($body['success']);
    }

    public function testDeletingFolderMovesMediaToRoot(): void
    {
        $folder = $this->createFolder('Temporary');
        $media = $this->createMedia();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $media->setFolder($folder);
        $entityManager->flush();

        $this->client->request('POST', sprintf('/admin/media/folders/%d/delete', $folder->getId()));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $entityManager->clear();
        $refreshed = static::getContainer()->get(MediaRepository::class)->find($media->getId());
        self::assertNotNull($refreshed);
        self::assertNull($refreshed->getFolder());
    }
}
