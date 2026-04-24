<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\PostSlugHistoryRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\Concern\BuildsPostPayload;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostSlugHistoryTest extends IntegrationTestCase
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
