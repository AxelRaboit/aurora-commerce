<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\Concern\BuildsPostPayload;
use App\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostsControllerTest extends IntegrationTestCase
{
    use BuildsPostPayload;

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

    private function firstPost(): Post
    {
        $post = static::getContainer()->get(PostRepository::class)->findOneBy([]);
        self::assertInstanceOf(Post::class, $post);

        return $post;
    }

    public function testEditWithMatchingVersionSucceeds(): void
    {
        $post = $this->firstPost();
        $originalVersion = $post->getVersion();

        [$statusCode, $body] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $originalVersion, newTitle: 'Updated title'),
        );

        self::assertSame(200, $statusCode);
        self::assertTrue($body['success'] ?? false);
        self::assertSame($originalVersion + 1, $body['post']['version']);
    }

    public function testEditWithStaleVersionReturnsConflict(): void
    {
        $post = $this->firstPost();

        [$statusCode, $body] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $post->getVersion() - 1, newTitle: 'Should be rejected'),
        );

        self::assertSame(409, $statusCode);
        self::assertFalse($body['success'] ?? true);
        self::assertTrue($body['conflict'] ?? false);
    }

    public function testEditWithForceBypassesVersionCheck(): void
    {
        $post = $this->firstPost();
        $originalVersion = $post->getVersion();

        [$statusCode, $body] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $originalVersion - 5, force: true, newTitle: 'Forced save'),
        );

        self::assertSame(200, $statusCode);
        self::assertTrue($body['success'] ?? false);
        self::assertSame($originalVersion + 1, $body['post']['version']);
    }

    public function testVersionIncrementsWhenOnlyTranslationsChange(): void
    {
        $post = $this->firstPost();
        $originalVersion = $post->getVersion();

        [$statusCode, $body] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $originalVersion, newTitle: 'Only title changed'),
        );

        self::assertSame(200, $statusCode);
        self::assertSame($originalVersion + 1, $body['post']['version']);

        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $refreshed = static::getContainer()->get(PostRepository::class)->find($post->getId());
        self::assertSame($originalVersion + 1, $refreshed->getVersion());
    }

    public function testEditWithoutVersionFieldSkipsConflictCheck(): void
    {
        $post = $this->firstPost();

        [$statusCode, $body] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, version: null, newTitle: 'No version sent'),
        );

        self::assertSame(200, $statusCode);
        self::assertTrue($body['success'] ?? false);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function newEditorialStatusesProvider(): iterable
    {
        yield 'pending_review' => ['pending_review'];
        yield 'scheduled' => ['scheduled'];
        yield 'archived' => ['archived'];
    }

    #[DataProvider('newEditorialStatusesProvider')]
    public function testEditAcceptsNewEditorialStatus(string $status): void
    {
        $post = $this->firstPost();

        $payload = $this->postPayload($post, $post->getVersion());
        $payload['status'] = $status;
        if ('scheduled' === $status) {
            $payload['scheduledAt'] = (new DateTimeImmutable('+1 day'))->format(DATE_ATOM);
        }

        [$statusCode, $body] = $this->editPost($post->getId(), $payload);

        self::assertSame(200, $statusCode, json_encode($body));
        self::assertTrue($body['success'] ?? false);
        self::assertSame($status, $body['post']['status']);

        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $refreshed = static::getContainer()->get(PostRepository::class)->find($post->getId());
        self::assertSame($status, $refreshed->getStatus()->value);
    }

    public function testEditRejectsUnknownStatus(): void
    {
        $post = $this->firstPost();

        $payload = $this->postPayload($post, $post->getVersion());
        $payload['status'] = 'bogus';

        [$statusCode, $body] = $this->editPost($post->getId(), $payload);

        self::assertSame(200, $statusCode);
        self::assertFalse($body['success'] ?? true);
        self::assertArrayHasKey('errors', $body);
        self::assertArrayHasKey('status', $body['errors']);
    }
}
