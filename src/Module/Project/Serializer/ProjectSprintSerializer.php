<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectSprintInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectSprintSerializerInterface::class)]
class ProjectSprintSerializer implements ProjectSprintSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectSprintInterface $sprint): array
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
