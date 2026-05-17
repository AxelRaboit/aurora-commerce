<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagMergeInput
{
    /**
     * @param list<string> $sourceTags
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'notes.block.tags.errors.sources_empty')]
        #[Assert\All([
            new Assert\NotBlank(message: 'notes.block.tags.errors.empty'),
            new Assert\Length(max: 64),
        ])]
        public array $sourceTags,
        #[Assert\NotBlank(message: 'notes.block.tags.errors.empty')]
        #[Assert\Length(max: 64)]
        public string $targetTag,
    ) {}
}
