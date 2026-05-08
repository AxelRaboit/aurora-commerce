<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskItemsInputFactoryInterface::class)]
class ProjectTaskItemsInputFactory implements ProjectTaskItemsInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectTaskItemsInputInterface
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

        return new ProjectTaskItemsInput(items: $items);
    }
}
