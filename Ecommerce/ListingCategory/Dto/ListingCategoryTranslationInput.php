<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Dto;

use Aurora\Core\Support\Str;

final readonly class ListingCategoryTranslationInput
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
        public ?string $seoTitle,
        public ?string $seoDescription,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            slug: Str::trimOrNullFromArray($data, 'slug'),
            description: Str::trimOrNullFromArray($data, 'description'),
            seoTitle: Str::trimOrNullFromArray($data, 'seoTitle'),
            seoDescription: Str::trimOrNullFromArray($data, 'seoDescription'),
        );
    }
}
