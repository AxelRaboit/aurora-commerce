<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ListingTagInput implements ListingTagInputInterface
{
    /**
     * @param array<string, ListingTagTranslationInput> $translations
     */
    public function __construct(
        #[Assert\NotBlank(message: 'ecommerce.listing_tags.errors.color_required')]
        #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'ecommerce.listing_tags.errors.color_invalid')]
        public readonly string $color = '#6366F1',
        public readonly bool $isVisible = true,
        #[Assert\NotBlank(message: 'ecommerce.listing_tags.errors.translations_required')]
        public readonly array $translations = [],
    ) {}

    public function getColor(): string
    {
        return $this->color;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    #[Assert\Callback]
    public function validateTranslations(ExecutionContextInterface $context): void
    {
        if ([] === $this->translations) {
            $context->buildViolation('ecommerce.listing_tags.errors.translations_required')
                ->atPath('translations')
                ->addViolation();

            return;
        }

        $hasName = false;
        foreach ($this->translations as $locale => $translation) {
            if ('' !== $translation->name) {
                $hasName = true;
            } else {
                $context->buildViolation('ecommerce.listing_tags.errors.name_required')
                    ->atPath('translations['.$locale.'].name')
                    ->addViolation();
            }
        }

        if (!$hasName) {
            $context->buildViolation('ecommerce.listing_tags.errors.translations_required')
                ->atPath('translations')
                ->addViolation();
        }
    }
}
