<?php

declare(strict_types=1);

namespace App\Tests\Integration\Concern;

use App\Entity\Post;

/**
 * Helpers for integration tests that interact with the Posts API.
 */
trait BuildsPostPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function postPayload(Post $post, ?int $version, bool $force = false, ?string $newTitle = null): array
    {
        $translations = [];
        foreach ($post->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'title' => $newTitle ?? $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'blocks' => $translation->getBlocks(),
                'metaTitle' => $translation->getMetaTitle(),
                'metaDescription' => $translation->getMetaDescription(),
                'customFields' => $translation->getCustomFields(),
            ];
        }

        $payload = [
            'postTypeId' => $post->getPostType()->getId(),
            'status' => $post->getStatus()->value,
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'tagIds' => $post->getTags()->map(fn ($tag) => $tag->getId())->toArray(),
            'translations' => $translations,
        ];

        if (null !== $version) {
            $payload['version'] = $version;
        }
        if ($force) {
            $payload['force'] = true;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    protected function editPost(int $postId, array $payload): array
    {
        $this->client->request(
            'POST',
            sprintf('/admin/posts/%d/edit', $postId),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        return [
            $this->client->getResponse()->getStatusCode(),
            json_decode((string) $this->client->getResponse()->getContent(), true) ?? [],
        ];
    }
}
