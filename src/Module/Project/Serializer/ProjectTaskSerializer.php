<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTask;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectTaskSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function serialize(ProjectTask $task): array
    {
        return [
            'id' => $task->getId(),
            'reference' => $task->getReference(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'columnId' => $task->getColumn()->getId(),
            'priority' => $task->getPriority()->value,
            'priorityLabel' => $this->translator->trans($task->getPriority()->getLabelKey()),
            'assignee' => $task->getAssignee() instanceof User ? [
                'id' => $task->getAssignee()->getId(),
                'name' => $task->getAssignee()->getName(),
            ] : null,
            'dueDate' => $task->getDueDate()?->format('Y-m-d'),
            'position' => $task->getPosition(),
            'createdAt' => $task->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $task->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
