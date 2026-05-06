<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Concern;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Editorial\Post\Entity\Post;

/**
 * Helpers for integration tests that interact with the Posts API.
 *
 * Consumers must declare `$this->client` (KernelBrowser) and `$this->urlGenerator`
 * (UrlGeneratorInterface) — `editPost()` relies on both.
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
            'scheduledAt' => $post->getScheduledAt()?->format(DATE_ATOM),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'termIds' => $post->getTerms()->map(fn ($term) => $term->getId())->toArray(),
            'relatedPostIds' => $post->getRelatedPosts()->map(fn ($related) => $related->getId())->toArray(),
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
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate('backend_posts_edit', ['id' => $postId]),
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
