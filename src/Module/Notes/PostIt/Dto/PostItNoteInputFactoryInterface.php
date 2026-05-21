<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Dto;

interface PostItNoteInputFactoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): PostItNoteInputInterface;
}
