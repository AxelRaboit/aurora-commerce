<?php

declare(strict_types=1);

namespace App\DTO;

use App\Support\Str;

final readonly class PostTranslationInput
{
    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, mixed>             $customFields
     */
    public function __construct(
        public ?string $title,
        public ?string $slug,
        public array $blocks,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public array $customFields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: Str::trimOrNull((string) ($data['title'] ?? '')),
            slug: Str::trimOrNull((string) ($data['slug'] ?? '')),
            blocks: is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
            metaTitle: Str::trimOrNull((string) ($data['metaTitle'] ?? '')),
            metaDescription: Str::trimOrNull((string) ($data['metaDescription'] ?? '')),
            customFields: is_array($data['customFields'] ?? null) ? $data['customFields'] : [],
        );
    }
}
