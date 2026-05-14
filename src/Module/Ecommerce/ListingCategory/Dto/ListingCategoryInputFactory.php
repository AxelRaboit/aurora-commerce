<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingCategoryInputFactoryInterface::class)]
class ListingCategoryInputFactory implements ListingCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingCategoryInputInterface
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $translationData) {
            if (is_array($translationData)) {
                $translations[(string) $locale] = ListingCategoryTranslationInput::fromArray($translationData);
            }
        }

        return new ListingCategoryInput(
            parentId: isset($data['parentId']) && '' !== (string) $data['parentId'] && (int) $data['parentId'] > 0 ? (int) $data['parentId'] : null,
            position: isset($data['position']) ? (int) $data['position'] : 0,
            imageId: isset($data['imageId']) && '' !== (string) $data['imageId'] && (int) $data['imageId'] > 0 ? (int) $data['imageId'] : null,
            isVisible: (bool) ($data['isVisible'] ?? true),
            translations: $translations,
        );
    }
}
