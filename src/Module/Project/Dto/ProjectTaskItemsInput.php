<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

/**
 * Bulk-replace payload for a task's checklist. The client sends the desired
 * full list — the manager wipes existing items and recreates them.
 */
class ProjectTaskItemsInput implements ProjectTaskItemsInputInterface
{
    /** @param list<array{label: string, done: bool}> $items */
    public function __construct(public readonly array $items = []) {}

    public function getItems(): array
    {
        return $this->items;
    }
}
