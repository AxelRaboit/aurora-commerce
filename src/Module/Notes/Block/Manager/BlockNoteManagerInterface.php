<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Block\Dto\BlockNoteInputInterface;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;

interface BlockNoteManagerInterface
{
    public function create(CoreUserInterface $user, BlockNoteInputInterface $input): BlockNoteInterface;

    public function update(BlockNoteInterface $note, BlockNoteInputInterface $input): void;

    public function delete(BlockNoteInterface $note): void;

    public function move(BlockNoteInterface $note, ?BlockNoteInterface $parent): void;

    /**
     * Reorder a whole sub-tree of block notes in one shot. Same semantics
     * as MarkdownNoteManager::reorder — cycles are detected atomically
     * before any mutation.
     *
     * @param list<array{id: int, parentId: ?int, position: int}> $entries
     */
    public function reorder(CoreUserInterface $user, array $entries): void;

    /**
     * Histogram of tag → number of the user's block notes carrying it,
     * sorted alphabetically (natural, case-insensitive).
     *
     * @return array<string, int>
     */
    public function tagCounts(CoreUserInterface $user): array;

    /**
     * Match $query against the user's block notes — scans decrypted block
     * payloads (text-shaped fields only) and titles, case-insensitive.
     * Empty or whitespace-only queries return an empty list.
     *
     * @return list<int>
     */
    public function searchContent(CoreUserInterface $user, string $query): array;

    public function renameTag(CoreUserInterface $user, string $oldTag, string $newTag): int;

    /** @param list<string> $sourceTags */
    public function mergeTags(CoreUserInterface $user, array $sourceTags, string $targetTag): int;

    public function removeTag(CoreUserInterface $user, string $tag): int;
}
