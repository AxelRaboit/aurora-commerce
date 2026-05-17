<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostsTrashControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
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

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_delete', ['id' => $postId]));
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

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_delete', ['id' => $postId]));
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_restore', ['id' => $postId]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $repository = static::getContainer()->get(PostRepository::class);
        $reloaded = $repository->find($postId);
        self::assertFalse($reloaded->isTrashed());
        self::assertNull($reloaded->getDeletedAt());
    }

    public function testForceDeleteRemovesPost(): void
    {
        $postId = $this->createPost()->getId();

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_delete', ['id' => $postId]));
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_force_delete', ['id' => $postId]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $repository = static::getContainer()->get(PostRepository::class);
        self::assertNull($repository->find($postId));
    }

    public function testListingExcludesTrashedByDefault(): void
    {
        $trashedId = $this->createPost()->getId();
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_posts_delete', ['id' => $trashedId]));

        $repository = static::getContainer()->get(PostRepository::class);

        $active = $repository->findPaginated(1, LocaleEnum::default()->value);
        $activeIds = array_map(static fn (Post $post): ?int => $post->getId(), $active['items']);
        self::assertNotContains($trashedId, $activeIds);

        $trash = $repository->findPaginated(1, LocaleEnum::default()->value, trashed: true);
        $trashIds = array_map(static fn (Post $post): ?int => $post->getId(), $trash['items']);
        self::assertContains($trashedId, $trashIds);
    }
}
