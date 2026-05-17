<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagRenameInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'notes.block.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $oldTag,
        #[Assert\NotBlank(message: 'notes.block.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $newTag,
    ) {}
}
