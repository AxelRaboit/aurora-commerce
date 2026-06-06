<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Dto;

use Aurora\Core\Support\Str;

final readonly class ListingTagTranslationInput
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            slug: Str::trimOrNullFromArray($data, 'slug'),
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
