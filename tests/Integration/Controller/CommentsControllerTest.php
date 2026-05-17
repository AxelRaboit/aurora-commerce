<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CommentsControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private Comment $comment;
    private Post $post;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);

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
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_comments_list'));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);
    }

    public function testListFilterByStatus(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_comments_list', ['status' => 'pending']));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);

        foreach ($body['items'] as $item) {
            self::assertSame('pending', $item['status']);
        }
    }

    public function testApproveComment(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_comments_approve', ['id' => $this->comment->getId()]));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('approved', $body['comment']['status']);
    }

    public function testSpamComment(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_comments_spam', ['id' => $this->comment->getId()]));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('spam', $body['comment']['status']);
    }

    public function testDeleteComment(): void
    {
        $commentId = $this->comment->getId();
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_comments_delete', ['id' => $commentId]));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
    }

    public function testToggleModeration(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_comments_toggle_moderation'));
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertArrayHasKey('moderationEnabled', $body);
    }
}
