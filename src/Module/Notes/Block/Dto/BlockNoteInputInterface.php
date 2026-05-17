<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

interface BlockNoteInputInterface
{
    public function getParentId(): ?int;

    public function getTitle(): ?string;

    /** @return list<string> */
    public function getTags(): array;

    public function getPosition(): ?int;

    /** @return list<BlockInput>|null */
    public function getBlocks(): ?array;
}
