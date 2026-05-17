<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(BlockNoteInputFactoryInterface::class)]
class BlockNoteInputFactory implements BlockNoteInputFactoryInterface
{
    public function fromArray(array $data): BlockNoteInputInterface
    {
        return new BlockNoteInput(
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            title: Str::trimOrNullFromArray($data, 'title'),
            tags: $this->stringList($data['tags'] ?? []),
            position: isset($data['position']) ? (int) $data['position'] : null,
            blocks: $this->blocksOrNull($data['blocks'] ?? null),
        );
    }

    /**
     * @return list<BlockInput>|null
     */
    private function blocksOrNull(mixed $raw): ?array
    {
        if (null === $raw) {
            return null;
        }

        if (!is_array($raw)) {
            return [];
        }

        $blocks = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $type = is_string($entry['type'] ?? null) ? $entry['type'] : '';
            $payload = is_array($entry['data'] ?? null) ? $entry['data'] : [];
            $id = is_string($entry['id'] ?? null) ? $entry['id'] : null;

            $blocks[] = new BlockInput(type: $type, data: $payload, id: $id);
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $tags = [];
        foreach ($raw as $tag) {
            if (!is_string($tag)) {
                continue;
            }

            $trimmed = mb_trim($tag);
            if ('' === $trimmed) {
                continue;
            }

            $tags[] = $trimmed;
        }

        return array_values(array_unique($tags));
    }
}
