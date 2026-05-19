<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\Concern\BuildsPostPayload;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostSlugHistoryTest extends IntegrationTestCase
{
    use BuildsPostPayload;

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

    private function firstPost(): Post
    {
        $post = static::getContainer()->get(PostRepository::class)->findOneBy([]);
        self::assertInstanceOf(Post::class, $post);

        return $post;
    }

    public function testChangingSlugRecordsHistoryEntry(): void
    {
        $post = $this->firstPost();
        $locale = array_key_first($post->getTranslations()->toArray());
        $originalSlug = $post->getTranslation($locale)->getSlug();

        $payload = $this->postPayload($post, $post->getVersion());
        $payload['translations'][$locale]['slug'] = 'totally-new-slug';

        [$statusCode] = $this->editPost($post->getId(), $payload);
        self::assertSame(200, $statusCode);

        $slugHistory = static::getContainer()->get(PostSlugHistoryRepository::class);
        self::assertNotNull(
            $slugHistory->findOneByLocaleAndSlug($locale, $originalSlug),
            'Previous slug should be recorded in history',
        );
    }

    public function testRenamingBackToPreviousSlugClearsStaleHistory(): void
    {
        $post = $this->firstPost();
        $locale = array_key_first($post->getTranslations()->toArray());
        $originalSlug = $post->getTranslation($locale)->getSlug();

        // rename -> generates history of original
        $payload = $this->postPayload($post, $post->getVersion());
        $payload['translations'][$locale]['slug'] = 'temp-slug';
        $this->editPost($post->getId(), $payload);

        // rename back to original -> history entry of original must be removed to avoid self-redirect
        $reloaded = static::getContainer()->get(PostRepository::class)->find($post->getId());
        $payload = $this->postPayload($reloaded, $reloaded->getVersion());
        $payload['translations'][$locale]['slug'] = $originalSlug;
        $this->editPost($reloaded->getId(), $payload);

        $slugHistory = static::getContainer()->get(PostSlugHistoryRepository::class);
        self::assertNull(
            $slugHistory->findOneByLocaleAndSlug($locale, $originalSlug),
            'History entry for the reclaimed slug should be cleared',
        );
    }
}
