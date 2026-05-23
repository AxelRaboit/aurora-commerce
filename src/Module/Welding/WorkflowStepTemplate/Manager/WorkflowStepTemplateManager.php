<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WorkflowStepTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WorkflowStepTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepTemplateManagerInterface::class)]
class WorkflowStepTemplateManager implements WorkflowStepTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WorkflowTemplateRepository $workflowTemplateRepository,
        protected readonly WorkflowStepTemplateRepository $stepRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WorkflowStepTemplateInputInterface $input): WorkflowStepTemplateInterface
    {
        $step = $this->createWorkflowStepTemplate();
        $this->applyInput($step, $input);

        $this->entityManager->persist($step);
        $this->entityManager->flush();

        $this->auditCreated($step);

        return $step;
    }

    public function update(WorkflowStepTemplateInterface $step, WorkflowStepTemplateInputInterface $input): void
    {
        $this->applyInput($step, $input);
        $this->entityManager->flush();

        $this->auditUpdated($step);
    }

    public function delete(WorkflowStepTemplateInterface $step): void
    {
        $this->auditDeleted($step);

        $this->entityManager->remove($step);
        $this->entityManager->flush();
    }

    public function reorder(array $orderedStepIds): void
    {
        if ([] === $orderedStepIds) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($orderedStepIds): void {
            $steps = $this->stepRepository->findBy(['id' => $orderedStepIds]);
            $stepsById = [];
            foreach ($steps as $step) {
                $stepsById[$step->getId()] = $step;
            }

            foreach ($orderedStepIds as $position => $stepId) {
                $step = $stepsById[$stepId] ?? null;
                if (null === $step) {
                    throw new RuntimeException(sprintf('WorkflowStepTemplate #%d not found', $stepId));
                }
                $step->setPosition($position);
            }

            $this->entityManager->flush();
        });

        $this->auditLogger->log('welding', 'workflow_step_template.reordered', 'WorkflowStepTemplate', null, [
            'orderedStepIds' => $orderedStepIds,
        ]);
    }

    protected function createWorkflowStepTemplate(): WorkflowStepTemplateInterface
    {
        return new WorkflowStepTemplate();
    }

    protected function applyInput(WorkflowStepTemplateInterface $step, WorkflowStepTemplateInputInterface $input): void
    {
        $workflowTemplateId = $input->getWorkflowTemplateId();
        if (null === $step->getWorkflowTemplate() && null !== $workflowTemplateId) {
            $workflowTemplate = $this->workflowTemplateRepository->find($workflowTemplateId);
            if (null === $workflowTemplate) {
                throw new RuntimeException(sprintf('WorkflowTemplate #%d not found', $workflowTemplateId));
            }
            $step->setWorkflowTemplate($workflowTemplate);
        }

        $step->setPosition($input->getPosition());
        $step->setTitle($input->getTitle());
        $step->setDescription($input->getDescription());
        $step->setRequiresValidation($input->isRequiresValidation());
        $step->setValidatorRole($input->isRequiresValidation() ? $input->getValidatorRole() : null);
    }

    protected function auditCreated(WorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.created', 'WorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditUpdated(WorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.updated', 'WorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditDeleted(WorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.deleted', 'WorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditPayload(WorkflowStepTemplateInterface $step): array
    {
        return [
            'title' => $step->getTitle(),
            'position' => $step->getPosition(),
            'requiresValidation' => $step->getRequiresValidation(),
            'workflowTemplateId' => $step->getWorkflowTemplate()?->getId(),
        ];
    }
}
