<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Media\Library\Entity\Media;
use Aurora\Core\Media\Library\Entity\MediaFolder;
use Aurora\Core\Media\Library\Repository\MediaFolderRepository;
use Aurora\Core\Media\Library\Repository\MediaRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\Concern\CreatesTestUsers;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MediaActionsTest extends IntegrationTestCase
{
    use CreatesTestUsers;

    private KernelBrowser $client;
    private string $uploadDir;
    private Filesystem $filesystem;
    private UrlGeneratorInterface $urlGenerator;

    /** @var list<string> absolute paths created during the test, scrubbed in tearDown */
    private array $createdPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->loginAsAdmin();

        $this->uploadDir = static::getContainer()->getParameter('app.upload_dir');
        $this->filesystem = new Filesystem();
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            if ($this->filesystem->exists($path)) {
                $this->filesystem->remove($path);
            }
        }
        $this->createdPaths = [];

        parent::tearDown();
    }

    public function testEditMediaUpdatesAltCaptionAndFocalPoint(): void
    {
        $media = $this->uploadAndPersist('alt.png', 200, 150);

        [$status, $body] = $this->postJson('backend_media_update', ['id' => $media->getId()], [
            'alt' => 'A scenic banner',
            'caption' => 'Hero of the homepage',
            'focalX' => 0.4,
            'focalY' => 0.6,
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('A scenic banner', $body['media']['alt']);
        self::assertSame(0.4, $body['media']['focalX']);
        self::assertSame(0.6, $body['media']['focalY']);
    }

    public function testMoveAttachesMediaToFolderAndBackToRoot(): void
    {
        $folder = $this->createFolder('Marketing');
        $media = $this->uploadAndPersist('to-move.png', 200, 150);

        [, $body] = $this->postJson('backend_media_move', ['id' => $media->getId()], ['folderId' => $folder->getId()]);
        self::assertTrue($body['success']);
        self::assertSame($folder->getId(), $body['media']['folderId']);

        [, $rootBody] = $this->postJson('backend_media_move', ['id' => $media->getId()], ['folderId' => 0]);
        self::assertTrue($rootBody['success']);
        self::assertNull($rootBody['media']['folderId']);
    }

    public function testReorderUpdatesPositionsByIndex(): void
    {
        $first = $this->uploadAndPersist('first.png', 100, 100);
        $second = $this->uploadAndPersist('second.png', 100, 100);
        $third = $this->uploadAndPersist('third.png', 100, 100);

        [$status, $body] = $this->postJson('backend_media_reorder', [], [
            'ids' => [$third->getId(), $first->getId(), $second->getId()],
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $repository = static::getContainer()->get(MediaRepository::class);

        self::assertSame(0, $repository->find($third->getId())->getPosition());
        self::assertSame(1, $repository->find($first->getId())->getPosition());
        self::assertSame(2, $repository->find($second->getId())->getPosition());
    }

    public function testCropUpdatesMediaDimensions(): void
    {
        $media = $this->uploadAndPersist('crop.png', 800, 600);

        [$status, $body] = $this->postJson('backend_media_crop', ['id' => $media->getId()], [
            'x' => 100,
            'y' => 50,
            'width' => 400,
            'height' => 300,
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame(400, $body['media']['width']);
        self::assertSame(300, $body['media']['height']);
    }

    public function testBulkDeleteRemovesMediaAndFiles(): void
    {
        $a = $this->uploadAndPersist('bulk-a.png', 100, 100);
        $b = $this->uploadAndPersist('bulk-b.png', 100, 100);
        $aFile = Path::join($this->uploadDir, $a->getPath());
        $bFile = Path::join($this->uploadDir, $b->getPath());
        self::assertFileExists($aFile);
        self::assertFileExists($bFile);

        [$status, $body] = $this->postJson('backend_media_bulk_delete', [], ['ids' => [$a->getId(), $b->getId()]]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(MediaRepository::class);
        self::assertNull($repository->find($a->getId()));
        self::assertNull($repository->find($b->getId()));
        self::assertFileDoesNotExist($aFile);
        self::assertFileDoesNotExist($bFile);
    }

    public function testBulkMoveAttachesAllSelectedMediaToFolder(): void
    {
        $folder = $this->createFolder('Archive');
        $a = $this->uploadAndPersist('move-a.png', 100, 100);
        $b = $this->uploadAndPersist('move-b.png', 100, 100);

        [$status, $body] = $this->postJson('backend_media_bulk_move', [], [
            'ids' => [$a->getId(), $b->getId()],
            'folderId' => $folder->getId(),
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $repository = static::getContainer()->get(MediaRepository::class);
        self::assertSame($folder->getId(), $repository->find($a->getId())->getFolder()?->getId());
        self::assertSame($folder->getId(), $repository->find($b->getId())->getFolder()?->getId());
    }

    public function testFolderEditRenamesAndReparents(): void
    {
        $parent = $this->createFolder('Top');
        $child = $this->createFolder('Old name');

        [$status, $body] = $this->postJson('backend_media_folder_edit', ['id' => $child->getId()], [
            'name' => 'New name',
            'parentId' => $parent->getId(),
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $refreshed = static::getContainer()->get(MediaFolderRepository::class)->find($child->getId());
        self::assertSame('New name', $refreshed->getName());
        self::assertSame($parent->getId(), $refreshed->getParent()?->getId());
    }

    public function testPermalinkRedirectsToFileWithCacheBust(): void
    {
        $media = $this->uploadAndPersist('permalink.png', 200, 200);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('media_view', ['id' => $media->getId()]));
        $response = $this->client->getResponse();

        self::assertSame(302, $response->getStatusCode());
        $location = $response->headers->get('Location');
        self::assertNotNull($location);
        self::assertStringStartsWith('/uploads/', $location);
        self::assertStringContainsString('?v=', $location);
    }

    public function testPermalinkReturns404ForUnknownMedia(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('media_view', ['id' => 999999]));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testNonAdminCannotAccessMediaAdmin(): void
    {
        // ROLE_USER without media privilege should be denied
        $user = $this->createTestUser('plain-user', role: UserRoleEnum::User);
        $this->client->loginUser($user, 'admin');

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_media_list'));

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testUploadAndDeleteAreRecordedInAuditLog(): void
    {
        $media = $this->uploadAndPersist('audited.png', 200, 200);
        $mediaId = $media->getId();

        $auditRepository = static::getContainer()->get(AuditLogRepository::class);
        $uploadEntries = $auditRepository->findBy(['module' => 'media', 'action' => 'uploaded', 'entityId' => $mediaId]);
        self::assertCount(1, $uploadEntries);

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_delete', ['id' => $mediaId]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $deleteEntries = $auditRepository->findBy(['module' => 'media', 'action' => 'deleted', 'entityId' => $mediaId]);
        self::assertCount(1, $deleteEntries);
    }

    private function loginAsAdmin(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }

    private function uploadAndPersist(string $name, int $width, int $height): Media
    {
        $upload = $this->prepareUploadedFile($name, 'image/png', $width, $height);
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_upload'), [], ['image' => $upload]);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(1, $body['success']);

        $media = static::getContainer()->get(MediaRepository::class)->find($body['media']['id']);
        self::assertInstanceOf(Media::class, $media);

        $this->trackForCleanup(Path::join($this->uploadDir, $media->getPath()));
        foreach ($media->getVariants() as $variantPath) {
            $this->trackForCleanup(Path::join($this->uploadDir, $variantPath));
        }

        return $media;
    }

    private function prepareUploadedFile(string $originalName, string $mimeType, int $width, int $height): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'aurora_upload_');
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 50, 100, 150);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagepng($image, $tmp);
        imagedestroy($image);
        $this->trackForCleanup($tmp);

        return new UploadedFile($tmp, $originalName, $mimeType, null, true);
    }

    private function createFolder(string $name): MediaFolder
    {
        [$status, $body] = $this->postJson('backend_media_folder_create', [], ['name' => $name, 'parentId' => null]);
        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        $folder = static::getContainer()->get(MediaFolderRepository::class)->find($body['folder']['id']);
        self::assertInstanceOf(MediaFolder::class, $folder);

        return $folder;
    }

    /**
     * @param array<string, mixed> $routeParameters
     * @param array<string, mixed> $payload
     *
     * @return array{0: int, 1: array}
     */
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

        return [
            $this->client->getResponse()->getStatusCode(),
            json_decode((string) $this->client->getResponse()->getContent(), true) ?? [],
        ];
    }

    private function trackForCleanup(string $absolutePath): void
    {
        $this->createdPaths[] = $absolutePath;
    }
}
