<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\User;
use App\Enum\CommentStatusEnum;
use App\Enum\PostStatusEnum;
use App\Repository\PostTypeRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class CommentsControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private Comment $comment;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@velox.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $postType = static::getContainer()->get(PostTypeRepository::class)->findOneBy(['slug' => 'article']);
        self::assertNotNull($postType);

        $post = (new Post())->setPostType($postType)->setStatus(PostStatusEnum::Published);
        $translation = (new PostTranslation())
            ->setPost($post)
            ->setLocale('fr')
            ->setTitle('Test Comments Post')
            ->setSlug('test-comments-post-'.uniqid())
            ->setBlocks([]);
        $entityManager->persist($post);
        $entityManager->persist($translation);

        $comment = (new Comment())
            ->setPost($post)
            ->setAuthorName('Test Author')
            ->setAuthorEmail('author@example.com')
            ->setContent('Test comment content')
            ->setStatus(CommentStatusEnum::Approved);
        $entityManager->persist($comment);
        $entityManager->flush();

        $this->post = $post;
        $this->comment = $comment;
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $freshPost = $entityManager->find(Post::class, $this->post->getId());
        if (null !== $freshPost) {
            $entityManager->remove($freshPost);
            $entityManager->flush();
        }

        parent::tearDown();
    }

    public function testListReturnsOk(): void
    {
        $this->client->request('GET', '/admin/comments/list');
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
        self::assertIsArray($body['items']);
    }

    public function testListFilterByStatus(): void
    {
        $this->client->request('GET', '/admin/comments/list?status=pending');
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
        self::assertIsArray($body['items']);

        foreach ($body['items'] as $item) {
            self::assertSame('pending', $item['status']);
        }
    }

    public function testApproveComment(): void
    {
        $this->client->request('POST', sprintf('/admin/comments/%d/approve', $this->comment->getId()));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
        self::assertSame('approved', $body['comment']['status']);
    }

    public function testSpamComment(): void
    {
        $this->client->request('POST', sprintf('/admin/comments/%d/spam', $this->comment->getId()));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
        self::assertSame('spam', $body['comment']['status']);
    }

    public function testDeleteComment(): void
    {
        $commentId = $this->comment->getId();
        $this->client->request('DELETE', sprintf('/admin/comments/%d', $commentId));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
    }

    public function testToggleModeration(): void
    {
        $this->client->request('POST', '/admin/comments/toggle-moderation');
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
        self::assertArrayHasKey('moderationEnabled', $body);
    }
}
