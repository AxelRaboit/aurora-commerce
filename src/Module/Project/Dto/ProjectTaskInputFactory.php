<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskInputFactoryInterface::class)]
class ProjectTaskInputFactory implements ProjectTaskInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectTaskInputInterface
    {
        return new ProjectTaskInput(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            columnId: isset($data['columnId']) && '' !== (string) $data['columnId'] ? (int) $data['columnId'] : null,
            priority: isset($data['priority']) && '' !== $data['priority']
                ? (string) $data['priority']
                : ProjectTaskPriorityEnum::Medium->value,
            assigneeId: isset($data['assigneeId']) && '' !== (string) $data['assigneeId'] ? (int) $data['assigneeId'] : null,
            dueDate: Str::trimOrNullFromArray($data, 'dueDate'),
            position: isset($data['position']) ? (int) $data['position'] : 0,
            projectId: isset($data['projectId']) && '' !== (string) $data['projectId'] ? (int) $data['projectId'] : null,
            storyPoints: isset($data['storyPoints']) && '' !== (string) $data['storyPoints'] ? (int) $data['storyPoints'] : null,
            estimateMinutes: isset($data['estimateMinutes']) && '' !== (string) $data['estimateMinutes'] ? (int) $data['estimateMinutes'] : null,
            labelIds: $this->normalizeIdList($data['labelIds'] ?? []),
            watcherIds: $this->normalizeIdList($data['watcherIds'] ?? []),
            sprintId: isset($data['sprintId']) && '' !== (string) $data['sprintId'] ? (int) $data['sprintId'] : null,
        );
    }

    /** @return list<int> */
    protected function normalizeIdList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
