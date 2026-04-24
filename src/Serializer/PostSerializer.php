<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Post;
use DateTimeInterface;

final readonly class PostSerializer
{
    public function serialize(Post $post): array
    {
        $defaultTranslation = $post->getTranslation('fr');

        return [
            'id' => $post->getId(),
            'version' => $post->getVersion(),
            'status' => $post->getStatus()->value,
            'postType' => [
                'id' => $post->getPostType()->getId(),
                'label' => $post->getPostType()->getLabel(),
                'slug' => $post->getPostType()->getSlug(),
            ],
            'title' => $defaultTranslation?->getTitle(),
            'slug' => $defaultTranslation?->getSlug(),
            'tagIds' => $post->getTags()->map(fn (object $tag): ?int => $tag->getId())->toArray(),
            'publishedAt' => $post->getPublishedAt()?->format(DateTimeInterface::ATOM),
            'scheduledAt' => $post->getScheduledAt()?->format(DateTimeInterface::ATOM),
            'deletedAt' => $post->getDeletedAt()?->format(DateTimeInterface::ATOM),
            'trashed' => $post->isTrashed(),
            'createdAt' => $post->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public function serializeFull(Post $post): array
    {
        $translations = [];
        foreach ($post->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'title' => $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'blocks' => $translation->getBlocks(),
                'metaTitle' => $translation->getMetaTitle(),
                'metaDescription' => $translation->getMetaDescription(),
                'customFields' => $translation->getCustomFields(),
                'ogImageMediaId' => $translation->getOgImage()?->getId(),
                'ogImageUrl' => $translation->getOgImage()?->getPublicUrl(),
                'canonicalUrl' => $translation->getCanonicalUrl(),
                'noindex' => $translation->isNoindex(),
                'focusKeyword' => $translation->getFocusKeyword(),
                'jsonLd' => $translation->getJsonLd(),
            ];
        }

        return [
            ...$this->serialize($post),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'featuredMediaUrl' => $post->getFeaturedMedia()?->getPublicUrl(),
            'translations' => $translations,
        ];
    }
}
