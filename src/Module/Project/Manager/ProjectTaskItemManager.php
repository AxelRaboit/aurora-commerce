<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectTaskItemsInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Entity\ProjectTaskItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskItemManagerInterface::class)]
class ProjectTaskItemManager implements ProjectTaskItemManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Bulk-replace the task's checklist with the desired list. Simpler and more
     * predictable than diffing — checklists are short, the cost is negligible.
     */
    public function replaceForTask(ProjectTaskInterface $task, ProjectTaskItemsInputInterface $input): void
    {
        foreach ($task->getItems()->toArray() as $existing) {
            $this->entityManager->remove($existing);
        }

        $this->entityManager->flush();

        foreach ($input->getItems() as $position => $itemData) {
            $item = $this->createProjectTaskItem();
            $item->setTask($task)
                ->setLabel($itemData['label'])
                ->setDone($itemData['done'])
                ->setPosition($position);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.items.replaced', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'count' => count($input->getItems()),
        ]);
    }

    protected function createProjectTaskItem(): ProjectTaskItemInterface
    {
        return new ProjectTaskItem();
    }
}
