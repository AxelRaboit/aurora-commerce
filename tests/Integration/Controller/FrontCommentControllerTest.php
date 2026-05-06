<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FrontCommentControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private Post $post;
    private string $postSlug;
    private string $postTypeSlug;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        // createClient() must be called first to boot the kernel before accessing the container
        $this->client = static::createClient();

        // Enable comments via DBAL to ensure it's committed and visible across EM instances
        $conn = static::getContainer()->get(Connection::class);
        $conn->executeStatement(
            "INSERT INTO settings (setting_key, value, setting_type) VALUES ('comments_enabled', '1', 'string')
             ON CONFLICT (setting_key) DO UPDATE SET value = '1'",
        );

        // Create a published post directly via DBAL so it's committed and visible to the request EM
        $postTypeId = static::getContainer()->get(PostTypeRepository::class)
            ->findOneBy(['slug' => 'article'])
            ->getId();

        $postId = (int) $conn->fetchOne(
            "INSERT INTO posts (id, post_type_id, status) VALUES (NEXTVAL('seq_post_id'), ?, 'published') RETURNING id",
            [$postTypeId],
        );

        $this->postSlug = 'front-comment-test-'.uniqid();
        $this->postTypeSlug = 'article';

        $conn->executeStatement(
            "INSERT INTO post_translations (id, post_id, locale, title, slug, blocks, noindex, custom_fields) VALUES (NEXTVAL('seq_post_translation_id'), ?, 'fr', 'Front Comment Test', ?, '[]', false, '{}')",
            [$postId, $this->postSlug],
        );

        // Retrieve via repository so we have a proper entity
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->post = static::getContainer()->get(PostRepository::class)->find($postId);
        self::assertInstanceOf(Post::class, $this->post);

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    protected function tearDown(): void
    {
        $conn = static::getContainer()->get(Connection::class);
        $conn->executeStatement('DELETE FROM posts WHERE id = ?', [$this->post->getId()]);

        parent::tearDown();
    }

    public function testPostCommentCreatesComment(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('editorial_post_comment', [
            'locale' => 'fr',
            'postTypeSlug' => $this->postTypeSlug,
            'slug' => $this->postSlug,
        ]), [
            'authorName' => 'John Doe',
            'authorEmail' => 'john@example.com',
            'content' => 'This is a valid comment.',
        ]);

        $response = $this->client->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString($this->postSlug, (string) $response->headers->get('Location'));
    }

    public function testPostCommentWithInvalidDataReturnsErrors(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('editorial_post_comment', [
            'locale' => 'fr',
            'postTypeSlug' => $this->postTypeSlug,
            'slug' => $this->postSlug,
        ]), [
            'authorName' => '',
            'authorEmail' => 'valid@example.com',
            'content' => 'Some content',
        ]);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
    }

    public function testReactToComment(): void
    {
        $conn = static::getContainer()->get(Connection::class);
        $commentId = (int) $conn->fetchOne(
            "INSERT INTO comments (id, post_id, author_name, author_email, content, status, created_at)
             VALUES (NEXTVAL('seq_comment_id'), ?, 'Reactor', 'reactor@example.com', 'React to me', 'approved', NOW()) RETURNING id",
            [$this->post->getId()],
        );

        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('editorial_comment_react', [
                'locale' => 'fr',
                'postTypeSlug' => $this->postTypeSlug,
                'slug' => $this->postSlug,
                'commentId' => $commentId,
            ]),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'like']),
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertArrayHasKey('counts', $body);
        self::assertGreaterThanOrEqual(1, $body['counts']['like'] ?? 0);
    }
}
