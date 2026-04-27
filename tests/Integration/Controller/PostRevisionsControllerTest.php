<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Tests\Integration\Concern\BuildsPostPayload;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostRevisionsControllerTest extends IntegrationTestCase
{
    use BuildsPostPayload;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@aurora.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }

    private function firstPost(): Post
    {
        $post = static::getContainer()->get(PostRepository::class)->findOneBy([]);
        self::assertInstanceOf(Post::class, $post);

        return $post;
    }

    public function testEditingCreatesRevision(): void
    {
        $post = $this->firstPost();

        [$statusCode] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $post->getVersion(), newTitle: 'First edit'),
        );
        self::assertSame(200, $statusCode);

        $this->client->request('GET', sprintf('/admin/posts/%d/revisions', $post->getId()));
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertCount(1, $body['revisions']);
        self::assertNotEmpty($body['revisions'][0]['author']);
    }

    public function testRestoreRevertsPostToSnapshot(): void
    {
        $post = $this->firstPost();

        [$statusCode] = $this->editPost(
            $post->getId(),
            $this->postPayload($post, $post->getVersion(), newTitle: 'Title v2'),
        );
        self::assertSame(200, $statusCode);

        // fetch the first revision (which was the state after v2)
        $this->client->request('GET', sprintf('/admin/posts/%d/revisions', $post->getId()));
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $revisionId = $body['revisions'][0]['id'];

        // make another edit with a different title
        $reloaded = static::getContainer()->get(PostRepository::class)->find($post->getId());
        [$statusCode] = $this->editPost(
            $reloaded->getId(),
            $this->postPayload($reloaded, $reloaded->getVersion(), newTitle: 'Title v3'),
        );
        self::assertSame(200, $statusCode);

        // restore the first revision
        $this->client->request('POST', sprintf('/admin/posts/%d/revisions/%d/restore', $post->getId(), $revisionId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $restored = static::getContainer()->get(PostRepository::class)->find($post->getId());
        $defaultLocale = array_key_first($restored->getTranslations()->toArray());
        self::assertSame('Title v2', $restored->getTranslation($defaultLocale)->getTitle());
    }

    public function testRevisionsAreCappedByLimitParameter(): void
    {
        $post = $this->firstPost();

        $container = static::getContainer();
        $container->get(SettingRepository::class)->set('post_revisions_limit', '2');

        for ($i = 0; $i < 4; ++$i) {
            $reloaded = static::getContainer()->get(PostRepository::class)->find($post->getId());
            [$statusCode] = $this->editPost(
                $reloaded->getId(),
                $this->postPayload($reloaded, $reloaded->getVersion(), newTitle: 'Edit '.$i),
            );
            self::assertSame(200, $statusCode);
        }

        $this->client->request('GET', sprintf('/admin/posts/%d/revisions', $post->getId()));
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(2, $body['revisions']);
    }
}
