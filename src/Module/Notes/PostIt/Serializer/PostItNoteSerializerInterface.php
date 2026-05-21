<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Serializer;

use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;

interface PostItNoteSerializerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(PostItNoteInterface $note): array;
}
