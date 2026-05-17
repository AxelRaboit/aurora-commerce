<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Round-trip for the image upload + serve endpoints. Files land in the
 * real storage dir (`var/uploads/notes-markdown/{userId}`) — tearDown
 * cleans up the temp directory created for the admin user.
 */
final class MarkdownNotesImagesControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    private string $projectDir;
    /** @var list<string> */
    private array $createdUserDirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $this->createdUserDirs = [];
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        foreach ($this->createdUserDirs as $dir) {
            if (is_dir($dir)) {
                $filesystem->remove($dir);
            }
        }

        parent::tearDown();
    }

    public function testUploadStoresFileAndReturnsServeUrl(): void
    {
        $fixture = $this->makePngFixture();

        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_notes_markdown_images_upload'),
            parameters: [],
            files: ['image' => $fixture],
        );

        $response = $this->client->getResponse();
        $body = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode(), 'body: '.json_encode($body));
        self::assertTrue($body['success']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}\.png$/', $body['filename']);
        self::assertStringContainsString('/backend/notes/markdown/images/', $body['url']);

        $this->trackUserDir((int) $this->client->getRequest()->getUser()?->getId() ?? 0);
    }

    public function testUploadRejectsPdf(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf-');
        file_put_contents($path, "%PDF-1.4\n%fake\n");

        $fixture = new UploadedFile(
            path: $path,
            originalName: 'bad.pdf',
            mimeType: 'application/pdf',
            test: true,
        );

        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_notes_markdown_images_upload'),
            parameters: [],
            files: ['image' => $fixture],
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testServeReturnsBytesForOwner(): void
    {
        $filename = $this->uploadAndCapture();

        ob_start();
        $this->client->request(
            HttpMethodEnum::Get->value,
            $this->urlGenerator->generate('backend_notes_markdown_images_serve', ['filename' => $filename]),
        );
        ob_end_clean();

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testServeReturns404ForUnknownFilename(): void
    {
        $this->client->request(
            HttpMethodEnum::Get->value,
            $this->urlGenerator->generate('backend_notes_markdown_images_serve', ['filename' => 'nope.png']),
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Track the user upload dir for cleanup. Tests run against a real
     * filesystem path: `var/uploads/notes-markdown/{userId}`.
     */
    private function trackUserDir(int $userId): void
    {
        $dir = $this->projectDir.'/var/uploads/notes-markdown/'.$userId;
        if (!in_array($dir, $this->createdUserDirs, true)) {
            $this->createdUserDirs[] = $dir;
        }
    }

    /** Upload a fixture and return the stored filename for follow-up assertions. */
    private function uploadAndCapture(): string
    {
        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_notes_markdown_images_upload'),
            parameters: [],
            files: ['image' => $this->makePngFixture()],
        );

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), 'upload prep failed: '.json_encode($body));

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        $this->trackUserDir((int) $admin->getId());

        return $body['filename'];
    }

    private function makePngFixture(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'aurora-img-');
        $image = imagecreatetruecolor(1, 1);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile(
            path: $path,
            originalName: 'test.png',
            mimeType: 'image/png',
            test: true,
        );
    }
}
