<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Serializer;

use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;

interface BlockNoteSerializerInterface
{
    /**
     * Lightweight payload for tree/list views — excludes blocks for perf.
     *
     * @return array<string, mixed>
     */
    public function serializeListItem(BlockNoteInterface $note): array;

    /**
     * Full payload for the detail view — includes the ordered block list.
     *
     * @return array<string, mixed>
     */
    public function serializeDetail(BlockNoteInterface $note): array;

    /**
     * @param array{id?: string, type: string, data: array<string, mixed>} $block
     *
     * @return array<string, mixed>
     */
    public function serializeBlock(array $block): array;

    /**
     * @param array<string, int> $counts
     *
     * @return list<array{tag: string, count: int}>
     */
    public function serializeTagCounts(array $counts): array;
}
