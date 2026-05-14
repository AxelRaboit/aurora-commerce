<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Serializer;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingCategorySerializerInterface::class)]
class ListingCategorySerializer implements ListingCategorySerializerInterface
{
    public function serialize(ListingCategoryInterface $category, ?string $locale = null): array
    {
        $translations = [];
        foreach ($category->getTranslations() as $translationLocale => $translation) {
            $translations[(string) $translationLocale] = [
                'locale' => $translation->getLocale(),
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'description' => $translation->getDescription(),
                'seoTitle' => $translation->getSeoTitle(),
                'seoDescription' => $translation->getSeoDescription(),
            ];
        }

        $image = $category->getImage();

        return [
            'id' => $category->getId(),
            'parentId' => $category->getParent()?->getId(),
            'position' => $category->getPosition(),
            'isVisible' => $category->isVisible(),
            'depth' => $category->getDepth(),
            'hasChildren' => !$category->getChildren()->isEmpty(),
            'image' => $image instanceof MediaInterface ? [
                'id' => $image->getId(),
                'url' => $image->getPublicUrl(),
                'alt' => $image->getAlt(),
            ] : null,
            'translations' => $translations,
            'createdAt' => $category->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
