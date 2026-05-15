<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInputInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;

interface MarkdownNoteManagerInterface
{
    public function create(CoreUserInterface $user, MarkdownNoteInputInterface $input): MarkdownNoteInterface;

    public function update(MarkdownNoteInterface $note, MarkdownNoteInputInterface $input): void;

    public function delete(MarkdownNoteInterface $note): void;

    public function move(MarkdownNoteInterface $note, ?MarkdownNoteInterface $parent): void;

    /**
     * Reorder a flat list of note ids within their parent. The position of
     * each note matches its index in the array (0-based).
     *
     * @param list<int> $orderedIds
     */
    public function reorder(CoreUserInterface $user, array $orderedIds): void;
}
