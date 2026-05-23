<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTask;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTaskManagerInterface::class)]
class WeldingWorkflowStepTaskManager implements WeldingWorkflowStepTaskManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function snapshotFromTemplates(WeldingWorkflowStepInterface $step, iterable $templates): array
    {
        $created = [];

        foreach ($templates as $template) {
            $task = $this->createWorkflowStepTask();
            $task->setWorkflowStep($step);
            $task->setTaskTemplate($template);
            $task->setLabel($template->getLabel());
            $task->setDescription($template->getDescription());
            $task->setPosition($template->getPosition());
            $task->setRequired($template->getRequired());
            $task->setDone(false);

            $this->entityManager->persist($task);
            $created[] = $task;
        }

        return $created;
    }

    public function setDone(WeldingWorkflowStepTaskInterface $task, bool $done, CoreUserInterface $actor): void
    {
        $task->setDone($done);
        $task->setDoneBy($done ? $actor : null);
        $task->setDoneAt($done ? new DateTimeImmutable() : null);

        $this->entityManager->flush();

        $this->auditToggled($task, $actor);
    }

    protected function createWorkflowStepTask(): WeldingWorkflowStepTaskInterface
    {
        return new WeldingWorkflowStepTask();
    }

    protected function auditToggled(WeldingWorkflowStepTaskInterface $task, CoreUserInterface $actor): void
    {
        $this->auditLogger->log(
            'welding',
            $task->getDone() ? 'workflow_step_task.done' : 'workflow_step_task.undone',
            'WeldingWorkflowStepTask',
            $task->getId(),
            $this->auditPayload($task) + ['actorId' => $actor->getId()],
        );
    }

    /** @return array<string, mixed> */
    protected function auditPayload(WeldingWorkflowStepTaskInterface $task): array
    {
        return [
            'stepId' => $task->getWorkflowStep()?->getId(),
            'label' => $task->getLabel(),
            'done' => $task->getDone(),
            'required' => $task->getRequired(),
        ];
    }
}
