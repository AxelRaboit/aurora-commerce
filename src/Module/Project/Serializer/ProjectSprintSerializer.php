<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectSprint;

final readonly class ProjectSprintSerializer
{
    /** @return array<string, mixed> */
    public function serialize(ProjectSprint $sprint): array
    {
        return [
            'id' => $sprint->getId(),
            'name' => $sprint->getName(),
            'startDate' => $sprint->getStartDate()?->format('Y-m-d'),
            'endDate' => $sprint->getEndDate()?->format('Y-m-d'),
            'isActive' => $sprint->isActive(),
            'taskCount' => $sprint->getTasks()->count(),
        ];
    }
}
