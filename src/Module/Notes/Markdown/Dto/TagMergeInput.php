<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagMergeInput
{
    /**
     * @param list<string> $sourceTags
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'notes.markdown.tags.errors.sources_empty')]
        #[Assert\All([
            new Assert\NotBlank(message: 'notes.markdown.tags.errors.empty'),
            new Assert\Length(max: 64),
        ])]
        public array $sourceTags,
        #[Assert\NotBlank(message: 'notes.markdown.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $targetTag,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            sourceTags: self::stringList($data['sourceTags'] ?? null),
            targetTag: Str::trimFromArray($data, 'targetTag'),
        );
    }

    /** @return list<string> */
    private static function stringList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $tags = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }

            $trimmed = mb_trim($value);
            if ('' === $trimmed) {
                continue;
            }

            $tags[] = $trimmed;
        }

        return array_values(array_unique($tags));
    }
}
