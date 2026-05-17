<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BlockNoteReorderInput
{
    /**
     * @param list<array{id: int, parentId: ?int, position: int}> $entries
     */
    public function __construct(
        #[Assert\NotNull]
        public array $entries,
    ) {}
}
