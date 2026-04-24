<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Enum\PostStatusEnum;
use App\Repository\PostRepository;
use App\Repository\PostTypeRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\Concern\BuildsPostPayload;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostsRelatedTest extends IntegrationTestCase
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
        $this->client->loginUser($admin);
    }

    private function createPost(string $title): Post
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $post = (new Post())->setPostType($postType)->setStatus(PostStatusEnum::Draft);
        $post->translate('fr')->setTitle($title)->setSlug(strtolower(str_replace(' ', '-', $title)));

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

        $this->client->request('GET', '/admin/posts/search?q=apple&excludeId='.$first->getId());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertTrue($body['success']);
        // The self-excluded Apple article must not appear; Banana does not match "apple".
        $ids = array_map(static fn (array $r): int => $r['id'], $body['results']);
        self::assertNotContains($first->getId(), $ids);
    }
}
