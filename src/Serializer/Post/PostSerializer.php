<?php

declare(strict_types=1);

namespace App\Serializer\Post;

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
            'termIds' => $post->getTerms()->map(fn (object $term): ?int => $term->getId())->toArray(),
            'relatedPostIds' => $post->getRelatedPosts()->map(fn (Post $related): ?int => $related->getId())->toArray(),
            'publishedAt' => $post->getPublishedAt()?->format(DateTimeInterface::ATOM),
            'scheduledAt' => $post->getScheduledAt()?->format(DateTimeInterface::ATOM),
            'deletedAt' => $post->getDeletedAt()?->format(DateTimeInterface::ATOM),
            'trashed' => $post->isTrashed(),
            'commentsEnabled' => $post->isCommentsEnabled(),
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
                'ogImageFocalPosition' => $translation->getOgImage()?->getFocalPositionCss(),
                'canonicalUrl' => $translation->getCanonicalUrl(),
                'noindex' => $translation->isNoindex(),
                'focusKeyword' => $translation->getFocusKeyword(),
                'jsonLd' => $translation->getJsonLd(),
            ];
        }

        $relatedPosts = [];
        foreach ($post->getRelatedPosts() as $related) {
            $relatedPosts[] = [
                'id' => $related->getId(),
                'title' => $related->getTranslation('fr')?->getTitle() ?? ($related->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $related->getStatus()->value,
                'postType' => $related->getPostType()->getLabel(),
            ];
        }

        return [
            ...$this->serialize($post),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'featuredMediaUrl' => $post->getFeaturedMedia()?->getPublicUrl(),
            'featuredMediaFocalPosition' => $post->getFeaturedMedia()?->getFocalPositionCss(),
            'translations' => $translations,
            'relatedPosts' => $relatedPosts,
        ];
    }
}
