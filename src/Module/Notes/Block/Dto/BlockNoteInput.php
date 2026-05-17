<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BlockNoteInput implements BlockNoteInputInterface
{
    /**
     * @param list<string>          $tags
     * @param list<BlockInput>|null $blocks null means "do not touch the blocks
     *                                      collection" (e.g. a metadata-only
     *                                      update). An empty array clears it.
     */
    public function __construct(
        public readonly ?int $parentId = null,
        public readonly ?string $title = null,
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 64)])]
        public readonly array $tags = [],
        #[Assert\PositiveOrZero]
        public readonly ?int $position = null,
        #[Assert\Valid]
        public readonly ?array $blocks = null,
    ) {}

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getBlocks(): ?array
    {
        return $this->blocks;
    }
}
