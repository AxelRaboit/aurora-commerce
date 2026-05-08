<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectTaskInput implements ProjectTaskInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.title_required')]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',
        public readonly ?string $description = null,
        #[Assert\Positive]
        public readonly ?int $columnId = null,
        #[Assert\NotBlank(message: 'backend.projects.errors.priority_required')]
        #[Assert\Choice(callback: [ProjectTaskPriorityEnum::class, 'values'], message: 'backend.projects.errors.priority_invalid')]
        public readonly string $priority = ProjectTaskPriorityEnum::Medium->value,
        #[Assert\Positive]
        public readonly ?int $assigneeId = null,
        public readonly ?string $dueDate = null,
        public readonly int $position = 0,
        #[Assert\Positive]
        public readonly ?int $projectId = null,
        #[Assert\PositiveOrZero]
        public readonly ?int $storyPoints = null,
        #[Assert\PositiveOrZero]
        public readonly ?int $estimateMinutes = null,
        /** @var list<int> */
        #[Assert\All([new Assert\Positive()])]
        public readonly array $labelIds = [],
        /** @var list<int> */
        #[Assert\All([new Assert\Positive()])]
        public readonly array $watcherIds = [],
        #[Assert\Positive]
        public readonly ?int $sprintId = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getColumnId(): ?int
    {
        return $this->columnId;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getPriorityEnum(): ProjectTaskPriorityEnum
    {
        return ProjectTaskPriorityEnum::from($this->priority);
    }

    public function getAssigneeId(): ?int
    {
        return $this->assigneeId;
    }

    public function getDueDate(): ?string
    {
        return $this->dueDate;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getStoryPoints(): ?int
    {
        return $this->storyPoints;
    }

    public function getEstimateMinutes(): ?int
    {
        return $this->estimateMinutes;
    }

    public function getLabelIds(): array
    {
        return $this->labelIds;
    }

    public function getWatcherIds(): array
    {
        return $this->watcherIds;
    }

    public function getSprintId(): ?int
    {
        return $this->sprintId;
    }
}
