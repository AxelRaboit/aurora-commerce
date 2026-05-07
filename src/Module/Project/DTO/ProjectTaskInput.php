<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectTaskInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.title_required')]
        #[Assert\Length(max: 255)]
        public string $title = '',
        public ?string $description = null,
        #[Assert\Positive]
        public ?int $columnId = null,
        #[Assert\NotBlank(message: 'backend.projects.errors.priority_required')]
        #[Assert\Choice(callback: [ProjectTaskPriorityEnum::class, 'values'], message: 'backend.projects.errors.priority_invalid')]
        public string $priority = ProjectTaskPriorityEnum::Medium->value,
        #[Assert\Positive]
        public ?int $assigneeId = null,
        public ?string $dueDate = null,
        public int $position = 0,
        #[Assert\Positive]
        public ?int $projectId = null,
    ) {}

    public function priorityEnum(): ProjectTaskPriorityEnum
    {
        return ProjectTaskPriorityEnum::from($this->priority);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
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
        );
    }
}
