<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto\WeldingWorkflowStepTaskTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplate;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Repository\WeldingWorkflowStepTaskTemplateRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTaskTemplateManagerInterface::class)]
class WeldingWorkflowStepTaskTemplateManager implements WeldingWorkflowStepTaskTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingWorkflowStepTemplateRepository $stepRepository,
        protected readonly WeldingWorkflowStepTaskTemplateRepository $repository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WeldingWorkflowStepTaskTemplateInputInterface $input): WeldingWorkflowStepTaskTemplateInterface
    {
        $entry = $this->createWorkflowStepTaskTemplate();
        $this->applyInput($entry, $input);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditCreated($entry);

        return $entry;
    }

    public function update(WeldingWorkflowStepTaskTemplateInterface $entry, WeldingWorkflowStepTaskTemplateInputInterface $input): void
    {
        $this->applyInput($entry, $input);
        $this->entityManager->flush();

        $this->auditUpdated($entry);
    }

    public function delete(WeldingWorkflowStepTaskTemplateInterface $entry): void
    {
        $this->auditDeleted($entry);

        $this->entityManager->remove($entry);
        $this->entityManager->flush();
    }

    public function reorder(int $workflowStepTemplateId, array $orderedIds): void
    {
        $tasks = $this->repository->findBy(['workflowStepTemplate' => $workflowStepTemplateId]);
        $byId = [];
        foreach ($tasks as $task) {
            $byId[$task->getId()] = $task;
        }

        $position = 0;
        foreach ($orderedIds as $id) {
            if (!isset($byId[$id])) {
                continue;
            }

            $byId[$id]->setPosition($position++);
        }

        $this->entityManager->flush();
    }

    protected function createWorkflowStepTaskTemplate(): WeldingWorkflowStepTaskTemplateInterface
    {
        return new WeldingWorkflowStepTaskTemplate();
    }

    protected function applyInput(WeldingWorkflowStepTaskTemplateInterface $entry, WeldingWorkflowStepTaskTemplateInputInterface $input): void
    {
        if (!$entry->getWorkflowStepTemplate() instanceof WeldingWorkflowStepTemplateInterface && null !== $input->getWorkflowStepTemplateId()) {
            $step = $this->stepRepository->find($input->getWorkflowStepTemplateId());
            if (null === $step) {
                throw new RuntimeException(sprintf('WeldingWorkflowStepTemplate #%d not found', $input->getWorkflowStepTemplateId()));
            }

            $entry->setWorkflowStepTemplate($step);
        }

        $entry->setLabel($input->getLabel());
        $entry->setDescription($input->getDescription());
        $entry->setPosition($input->getPosition());
        $entry->setRequired($input->isRequired());
    }

    protected function auditCreated(WeldingWorkflowStepTaskTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_task_template.created', 'WeldingWorkflowStepTaskTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditUpdated(WeldingWorkflowStepTaskTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_task_template.updated', 'WeldingWorkflowStepTaskTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditDeleted(WeldingWorkflowStepTaskTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_task_template.deleted', 'WeldingWorkflowStepTaskTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(WeldingWorkflowStepTaskTemplateInterface $entry): array
    {
        return [
            'stepId' => $entry->getWorkflowStepTemplate()?->getId(),
            'label' => $entry->getLabel(),
            'position' => $entry->getPosition(),
            'required' => $entry->getRequired(),
        ];
    }
}
