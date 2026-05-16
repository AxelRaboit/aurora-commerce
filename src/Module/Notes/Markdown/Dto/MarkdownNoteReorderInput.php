<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MarkdownNoteReorderInput
{
    /**
     * @param list<array{id: int, parentId: ?int, position: int}> $entries
     */
    public function __construct(
        #[Assert\NotNull]
        public array $entries,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $raw = $data['entries'] ?? null;
        if (!is_array($raw)) {
            return new self(entries: []);
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

        return new self(entries: $entries);
    }
}
