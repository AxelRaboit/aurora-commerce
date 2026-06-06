<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Serializer;

use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingTagSerializerInterface::class)]
class ListingTagSerializer implements ListingTagSerializerInterface
{
    public function serialize(ListingTagInterface $tag, ?string $locale = null): array
    {
        $translations = [];
        foreach ($tag->getTranslations() as $translationLocale => $translation) {
            $translations[(string) $translationLocale] = [
                'locale' => $translation->getLocale(),
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'description' => $translation->getDescription(),
            ];
        }

        return [
            'id' => $tag->getId(),
            'color' => $tag->getColor(),
            'isVisible' => $tag->isVisible(),
            'translations' => $translations,
            'createdAt' => $tag->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $tag->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
