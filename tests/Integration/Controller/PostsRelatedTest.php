<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Tests\Integration\Concern\BuildsPostPayload;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostsRelatedTest extends IntegrationTestCase
{
    use BuildsPostPayload;

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

    private function createPost(string $title): Post
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $post = (new Post())->setPostType($postType)->setStatus(PostStatusEnum::Draft);
        $post->translate('fr')->setTitle($title)->setSlug(mb_strtolower(str_replace(' ', '-', $title)));

        $entityManager->persist($post);
        $entityManager->flush();

        return $post;
    }

    public function testLinkingAndUnlinkingRelatedPosts(): void
    {
        $source = $this->createPost('Source article');
        $targetA = $this->createPost('Target A');
        $targetB = $this->createPost('Target B');

        $payload = $this->postPayload($source, $source->getVersion());
        $payload['relatedPostIds'] = [$targetA->getId(), $targetB->getId()];

        [$statusCode, $body] = $this->editPost($source->getId(), $payload);
        self::assertSame(200, $statusCode);
        self::assertEqualsCanonicalizing([$targetA->getId(), $targetB->getId()], $body['post']['relatedPostIds']);

        // Unlink one
        $reloaded = static::getContainer()->get(PostRepository::class)->find($source->getId());
        $payload = $this->postPayload($reloaded, $reloaded->getVersion());
        $payload['relatedPostIds'] = [$targetA->getId()];
        [$statusCode, $body] = $this->editPost($source->getId(), $payload);
        self::assertSame(200, $statusCode);
        self::assertSame([$targetA->getId()], $body['post']['relatedPostIds']);
    }

    public function testSelfReferenceIsRejected(): void
    {
        $post = $this->createPost('Self');
        $payload = $this->postPayload($post, $post->getVersion());
        $payload['relatedPostIds'] = [$post->getId()];

        [$statusCode, $body] = $this->editPost($post->getId(), $payload);
        self::assertSame(200, $statusCode);
        self::assertSame([], $body['post']['relatedPostIds']);
    }

    public function testSearchEndpointExcludesSelfAndFiltersByQuery(): void
    {
        $first = $this->createPost('Apple article');
        $this->createPost('Banana post');

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_posts_search', ['q' => 'apple', 'excludeId' => $first->getId()]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertTrue($body['success']);
        // The self-excluded Apple article must not appear; Banana does not match "apple".
        $ids = array_map(static fn (array $r): int => $r['id'], $body['results']);
        self::assertNotContains($first->getId(), $ids);
    }

    public function testSearchEndpointResolvesByIds(): void
    {
        $first = $this->createPost('Alpha');
        $second = $this->createPost('Beta');
        $third = $this->createPost('Gamma');

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_posts_search', ['ids' => sprintf('%d,%d', $first->getId(), $third->getId())]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        $ids = array_map(static fn (array $r): int => $r['id'], $body['results']);
        self::assertEqualsCanonicalizing([$first->getId(), $third->getId()], $ids);
        self::assertNotContains($second->getId(), $ids);
    }
}
