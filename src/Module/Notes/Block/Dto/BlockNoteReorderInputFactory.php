<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(BlockNoteReorderInputFactoryInterface::class)]
class BlockNoteReorderInputFactory implements BlockNoteReorderInputFactoryInterface
{
    public function fromArray(array $data): BlockNoteReorderInput
    {
        $raw = $data['entries'] ?? null;
        if (!is_array($raw)) {
            return new BlockNoteReorderInput(entries: []);
        }

        $entries = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!isset($entry['id'])) {
                continue;
            }

            $entries[] = [
                'id' => (int) $entry['id'],
                'parentId' => isset($entry['parentId']) && '' !== $entry['parentId']
                    ? (int) $entry['parentId']
                    : null,
                'position' => (int) ($entry['position'] ?? 0),
            ];
        }

        return new BlockNoteReorderInput(entries: $entries);
    }
}
