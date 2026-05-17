<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Media\Library\Entity\MediaFolder;
use Aurora\Module\Media\Library\Repository\MediaFolderRepository;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Core\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MediaUploadTest extends IntegrationTestCase
{
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

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

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

    public function testUploadValidPngCreatesMediaAndPersists(): void
    {
        $upload = $this->prepareUploadedFile('hero.png', 'image/png', 600, 400);

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_upload'), [], ['image' => $upload]);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertSame(1, $body['success']);
        self::assertArrayHasKey('media', $body);
        self::assertNotNull($body['media']['id']);
        self::assertSame(600, $body['media']['width']);
        self::assertSame(400, $body['media']['height']);

        $media = static::getContainer()->get(MediaRepository::class)->find($body['media']['id']);
        self::assertNotNull($media);
        self::assertSame('hero.png', $media->getOriginalName());
        self::assertSame('image/png', $media->getMimeType());

        $absoluteFile = Path::join($this->uploadDir, $media->getPath());
        self::assertFileExists($absoluteFile);
        $this->trackForCleanup($absoluteFile);
        foreach ($media->getVariants() as $variantPath) {
            $this->trackForCleanup(Path::join($this->uploadDir, $variantPath));
        }
    }

    public function testUploadRejectsInvalidMimeType(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'aurora_upload_');
        $this->filesystem->dumpFile($tmp, "not an image\n");
        $this->trackForCleanup($tmp);

        $upload = new UploadedFile($tmp, 'note.txt', 'text/plain', null, true);

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_upload'), [], ['image' => $upload]);

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(0, $body['success']);
    }

    public function testUploadWithoutFileReturnsBadRequest(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_upload'));

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(0, $body['success']);
    }

    public function testUploadAttachesToFolderWhenFolderIdProvided(): void
    {
        $folder = $this->createFolder('Banners');

        $upload = $this->prepareUploadedFile('banner.png', 'image/png', 300, 200);
        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_media_upload'),
            ['folderId' => (string) $folder->getId()],
            ['image' => $upload],
        );

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(1, $body['success']);

        $media = static::getContainer()->get(MediaRepository::class)->find($body['media']['id']);
        self::assertNotNull($media);
        self::assertSame($folder->getId(), $media->getFolder()?->getId());

        $this->trackForCleanup(Path::join($this->uploadDir, $media->getPath()));
        foreach ($media->getVariants() as $variantPath) {
            $this->trackForCleanup(Path::join($this->uploadDir, $variantPath));
        }
    }

    public function testDeleteMediaRemovesFileVariantsAndEntity(): void
    {
        $upload = $this->prepareUploadedFile('to-delete.png', 'image/png', 800, 600);
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_upload'), [], ['image' => $upload]);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $mediaId = $body['media']['id'];

        $repository = static::getContainer()->get(MediaRepository::class);
        $media = $repository->find($mediaId);
        self::assertNotNull($media);

        $absoluteFile = Path::join($this->uploadDir, $media->getPath());
        $variantAbsolutes = array_map(fn (string $relativePath): string => Path::join($this->uploadDir, $relativePath), $media->getVariants());
        self::assertFileExists($absoluteFile);

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_media_delete', ['id' => $mediaId]));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertNull($repository->find($mediaId));
        self::assertFileDoesNotExist($absoluteFile);
        foreach ($variantAbsolutes as $variantAbsolute) {
            self::assertFileDoesNotExist($variantAbsolute);
        }
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
        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_media_folder_create'),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => $name, 'parentId' => null]),
        );
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($body['success']);
        $folder = static::getContainer()->get(MediaFolderRepository::class)->find($body['folder']['id']);
        self::assertInstanceOf(MediaFolder::class, $folder);

        return $folder;
    }

    private function trackForCleanup(string $absolutePath): void
    {
        $this->createdPaths[] = $absolutePath;
    }
}
