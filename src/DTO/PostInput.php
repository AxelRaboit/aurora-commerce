<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\PostStatusEnum;
use App\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostInput
{
    /**
     * @param array<string, PostTranslationInput> $translations
     * @param array<int>                          $tagIds
     */
    public function __construct(
        #[Assert\NotNull(message: 'posts.errors.post_type_required')]
        #[Assert\Positive(message: 'posts.errors.post_type_required')]
        public int $postTypeId,
        #[Assert\NotBlank(message: 'posts.errors.status_required')]
        #[Assert\Choice(callback: [PostStatusEnum::class, 'values'], message: 'posts.errors.status_invalid')]
        public string $status,
        public ?int $featuredMediaId,
        public array $tagIds,
        public array $translations,
        public ?int $version = null,
        public bool $force = false,
    ) {}

    public static function fromArray(array $data): self
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $translationData) {
            if (is_array($translationData)) {
                $translations[(string) $locale] = PostTranslationInput::fromArray($translationData);
            }
        }

        $tagIds = array_values(array_filter(
            array_map(intval(...), is_array($data['tagIds'] ?? null) ? $data['tagIds'] : []),
            fn (int $tagId): bool => $tagId > 0,
        ));

        return new self(
            postTypeId: (int) ($data['postTypeId'] ?? 0),
            status: Str::trimOrNull((string) ($data['status'] ?? '')) ?? PostStatusEnum::Draft->value,
            featuredMediaId: isset($data['featuredMediaId']) && $data['featuredMediaId'] > 0 ? (int) $data['featuredMediaId'] : null,
            tagIds: $tagIds,
            translations: $translations,
            version: isset($data['version']) ? (int) $data['version'] : null,
            force: (bool) ($data['force'] ?? false),
        );
    }
}
