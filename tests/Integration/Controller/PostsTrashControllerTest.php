<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Enum\PostStatusEnum;
use App\Repository\PostRepository;
use App\Repository\PostTypeRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostsTrashControllerTest extends IntegrationTestCase
{
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

    private function createPost(): Post
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $post = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Draft);

        $entityManager->persist($post);
        $entityManager->flush();

        return $post;
    }

    public function testDeleteIsSoftDelete(): void
    {
        $postId = $this->createPost()->getId();

        $this->client->request('POST', sprintf('/admin/posts/%d/delete', $postId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $repository = static::getContainer()->get(PostRepository::class);
        $reloaded = $repository->find($postId);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isTrashed());
        self::assertNotNull($reloaded->getDeletedAt());
    }

    public function testRestoreClearsDeletedAt(): void
    {
        $postId = $this->createPost()->getId();

        $this->client->request('POST', sprintf('/admin/posts/%d/delete', $postId));
        $this->client->request('POST', sprintf('/admin/posts/%d/restore', $postId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $repository = static::getContainer()->get(PostRepository::class);
        $reloaded = $repository->find($postId);
        self::assertFalse($reloaded->isTrashed());
        self::assertNull($reloaded->getDeletedAt());
    }

    public function testForceDeleteRemovesPost(): void
    {
        $postId = $this->createPost()->getId();

        $this->client->request('POST', sprintf('/admin/posts/%d/delete', $postId));
        $this->client->request('POST', sprintf('/admin/posts/%d/force-delete', $postId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $repository = static::getContainer()->get(PostRepository::class);
        self::assertNull($repository->find($postId));
    }

    public function testListingExcludesTrashedByDefault(): void
    {
        $trashedId = $this->createPost()->getId();
        $this->client->request('POST', sprintf('/admin/posts/%d/delete', $trashedId));

        $repository = static::getContainer()->get(PostRepository::class);

        $active = $repository->findPaginated(1);
        $activeIds = array_map(static fn (Post $post): ?int => $post->getId(), $active['items']);
        self::assertNotContains($trashedId, $activeIds);

        $trash = $repository->findPaginated(1, trashed: true);
        $trashIds = array_map(static fn (Post $post): ?int => $post->getId(), $trash['items']);
        self::assertContains($trashedId, $trashIds);
    }
}
