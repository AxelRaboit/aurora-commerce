<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Serializer;

use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(BlockNoteSerializerInterface::class)]
class BlockNoteSerializer implements BlockNoteSerializerInterface
{
    public function serializeListItem(BlockNoteInterface $note): array
    {
        return [
            'id' => $note->getId(),
            'parentId' => $note->getParent()?->getId(),
            'title' => $note->getTitle(),
            'tags' => $note->getTags(),
            'position' => $note->getPosition(),
            'createdAt' => $note->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $note->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public function serializeDetail(BlockNoteInterface $note): array
    {
        return [
            ...$this->serializeListItem($note),
            'blocks' => array_map($this->serializeBlock(...), $note->getBlocks()),
        ];
    }

    /** @param array{id?: string, type: string, data: array<string, mixed>} $block */
    public function serializeBlock(array $block): array
    {
        $payload = ['type' => $block['type'], 'data' => $block['data']];
        if (isset($block['id'])) {
            return ['id' => $block['id'], ...$payload];
        }

        return $payload;
    }

    public function serializeTagCounts(array $counts): array
    {
        $tags = [];
        foreach ($counts as $tag => $count) {
            $tags[] = ['tag' => $tag, 'count' => $count];
        }

        return $tags;
    }
}
