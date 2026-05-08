<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;

interface ProjectTaskInputInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getColumnId(): ?int;

    public function getPriority(): string;

    public function getPriorityEnum(): ProjectTaskPriorityEnum;

    public function getAssigneeId(): ?int;

    public function getDueDate(): ?string;

    public function getPosition(): int;

    public function getProjectId(): ?int;

    public function getStoryPoints(): ?int;

    public function getEstimateMinutes(): ?int;

    /** @return list<int> */
    public function getLabelIds(): array;

    /** @return list<int> */
    public function getWatcherIds(): array;

    public function getSprintId(): ?int;
}
