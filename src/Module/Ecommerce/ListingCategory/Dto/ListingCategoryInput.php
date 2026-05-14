<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ListingCategoryInput implements ListingCategoryInputInterface
{
    /**
     * @param array<string, ListingCategoryTranslationInput> $translations
     */
    public function __construct(
        public readonly ?int $parentId = null,
        public readonly int $position = 0,
        public readonly ?int $imageId = null,
        public readonly bool $isVisible = true,
        #[Assert\NotBlank(message: 'ecommerce.listing_categories.errors.translations_required')]
        public readonly array $translations = [],
    ) {}

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getImageId(): ?int
    {
        return $this->imageId;
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
            $context->buildViolation('ecommerce.listing_categories.errors.translations_required')
                ->atPath('translations')
                ->addViolation();

            return;
        }

        foreach ($this->translations as $locale => $translation) {
            if ('' === $translation->name) {
                $context->buildViolation('ecommerce.listing_categories.errors.name_required')
                    ->atPath('translations['.$locale.'].name')
                    ->addViolation();
            }
        }
    }
}
