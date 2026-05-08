<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectTaskItemsInput;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectTaskItemManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Bulk-replace the task's checklist with the desired list. Simpler and more
     * predictable than diffing — checklists are short, the cost is negligible.
     */
    public function replaceForTask(ProjectTask $task, ProjectTaskItemsInput $input): void
    {
        foreach ($task->getItems()->toArray() as $existing) {
            $this->entityManager->remove($existing);
        }

        $this->entityManager->flush();

        foreach ($input->items as $position => $itemData) {
            $item = new ProjectTaskItem();
            $item->setTask($task)
                ->setLabel($itemData['label'])
                ->setDone($itemData['done'])
                ->setPosition($position);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.items.replaced', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'count' => count($input->items),
        ]);
    }
}
