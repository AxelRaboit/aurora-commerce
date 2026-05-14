<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingTagInputFactoryInterface::class)]
class ListingTagInputFactory implements ListingTagInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingTagInputInterface
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $translationData) {
            if (is_array($translationData)) {
                $translations[(string) $locale] = ListingTagTranslationInput::fromArray($translationData);
            }
        }

        $color = Str::trimFromArray($data, 'color', '#6366F1');
        if ('' === $color) {
            $color = '#6366F1';
        }

        return new ListingTagInput(
            color: $color,
            isVisible: (bool) ($data['isVisible'] ?? true),
            translations: $translations,
        );
    }
}
