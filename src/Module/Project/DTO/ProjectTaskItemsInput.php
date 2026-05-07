<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

/**
 * Bulk-replace payload for a task's checklist. The client sends the desired
 * full list — the manager wipes existing items and recreates them.
 */
final readonly class ProjectTaskItemsInput
{
    /** @param list<array{label: string, done: bool}> $items */
    public function __construct(public array $items = []) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawItems = (array) ($data['items'] ?? []);
        $items = [];
        foreach ($rawItems as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $label = mb_trim((string) ($itemData['label'] ?? ''));
            if ('' === $label) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'done' => (bool) ($itemData['done'] ?? false),
            ];
        }

        return new self(items: $items);
    }
}
