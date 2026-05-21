<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Manager;

use Aurora\Module\Notes\PostIt\Dto\PostItNoteInputInterface;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PostItNoteManagerInterface
{
    public function create(CoreUserInterface $user, PostItNoteInputInterface $input): PostItNoteInterface;

    public function update(PostItNoteInterface $note, PostItNoteInputInterface $input): void;

    public function delete(PostItNoteInterface $note): void;

    /**
     * Update only the position of a note (board drag-drop). Skips the full
     * applyInput path to avoid touching encrypted columns on every drag.
     */
    public function move(PostItNoteInterface $note, int $positionX, int $positionY): void;
}
