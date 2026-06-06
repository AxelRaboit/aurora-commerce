<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Dto;

interface ListingTagInputInterface
{
    public function getColor(): string;

    public function isVisible(): bool;

    /** @return array<string, ListingTagTranslationInput> */
    public function getTranslations(): array;
}
