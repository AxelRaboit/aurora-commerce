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
     * Reorder a whole sub-tree of notes in one shot. Each entry carries
     * the desired `parentId` and `position` for a given note id. Used by
     * the drag-drop UI which flattens the visible tree client-side.
     *
     * Detects cycles atomically on the intended state and throws
     * \InvalidArgumentException if any are found.
     *
     * @param list<array{id: int, parentId: ?int, position: int}> $entries
     */
    public function reorder(CoreUserInterface $user, array $entries): void;

    /**
     * Notes that contain a [[title]] wiki-link pointing to $note.
     * Case-insensitive title match.
     *
     * @return list<array{id: int, title: ?string}>
     */
    public function backlinks(CoreUserInterface $user, MarkdownNoteInterface $note): array;

    /**
     * Notes that mention $note's title in their content without using
     * the [[…]] wiki-link syntax. Case-insensitive substring match.
     *
     * @return list<array{id: int, title: ?string}>
     */
    public function unlinkedMentions(CoreUserInterface $user, MarkdownNoteInterface $note): array;

    /**
     * Wiki-link graph for the whole user's notes. Edges are extracted
     * from [[target]] occurrences resolved against existing titles.
     *
     * @return array{
     *     nodes: list<array{id: int, title: string}>,
     *     edges: list<array{source: int, target: int}>,
     * }
     */
    public function graph(CoreUserInterface $user): array;
}
