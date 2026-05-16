<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagRenameInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'notes.markdown.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $oldTag,
        #[Assert\NotBlank(message: 'notes.markdown.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $newTag,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            oldTag: Str::trimFromArray($data, 'oldTag'),
            newTag: Str::trimFromArray($data, 'newTag'),
        );
    }
}
