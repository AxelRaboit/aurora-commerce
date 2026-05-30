<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Serializer;

use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Service\DocumentUrlGenerator;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingCategorySerializerInterface::class)]
class ListingCategorySerializer implements ListingCategorySerializerInterface
{
    public function __construct(
        protected readonly DocumentUrlGenerator $documentUrlGenerator,
    ) {}

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
            'image' => $image instanceof DocumentInterface ? [
                'id' => $image->getId(),
                'url' => $this->documentUrlGenerator->publicUrl($image),
                'alt' => $image->getAlt(),
            ] : null,
            'translations' => $translations,
            'createdAt' => $category->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
