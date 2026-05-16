<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagDeleteInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'notes.markdown.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $tag,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(tag: Str::trimFromArray($data, 'tag'));
    }
}
