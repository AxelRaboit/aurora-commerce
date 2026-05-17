<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

interface BlockNoteReorderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): BlockNoteReorderInput;
}
