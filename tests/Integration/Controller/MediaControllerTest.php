<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MediaControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@aurora.app', 'type' => 'admin']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    private function postJson(string $route, array $routeParameters, array $payload): array
    {
        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate($route, $routeParameters),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        return [$this->client->getResponse()->getStatusCode(), json_decode((string) $this->client->getResponse()->getContent(), true) ?? []];
    }

    private function createFolder(string $name, ?int $parentId = null): MediaFolder
    {
        [, $body] = $this->postJson('backend_media_folder_create', [], ['name' => $name, 'parentId' => $parentId]);
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

    public function testAltIsOptionalOnEdit(): void
    {
        $media = $this->createMedia();

        [$status, $body] = $this->postJson('backend_media_edit', ['id' => $media->getId()], [
            'alt' => '',
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
    }

    public function testEditUpdatesFocalPointAndFolder(): void
    {
        $media = $this->createMedia();
        $folder = $this->createFolder('Banners');

        [$status, $body] = $this->postJson('backend_media_edit', ['id' => $media->getId()], [
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

        [$status, $body] = $this->postJson('backend_media_folder_edit', ['id' => $parent->getId()], [
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

        [$status, $body] = $this->postJson('backend_media_edit', ['id' => $media->getId()], [
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

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_folder_delete', ['id' => $folder->getId()]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $entityManager->clear();
        $refreshed = static::getContainer()->get(MediaRepository::class)->find($media->getId());
        self::assertNotNull($refreshed);
        self::assertNull($refreshed->getFolder());
    }
}
