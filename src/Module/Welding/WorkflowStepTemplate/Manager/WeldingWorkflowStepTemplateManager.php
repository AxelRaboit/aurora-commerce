<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WeldingWorkflowStepTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTemplateManagerInterface::class)]
class WeldingWorkflowStepTemplateManager implements WeldingWorkflowStepTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingWorkflowTemplateRepository $workflowTemplateRepository,
        protected readonly WeldingWorkflowStepTemplateRepository $stepRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WeldingWorkflowStepTemplateInputInterface $input): WeldingWorkflowStepTemplateInterface
    {
        $step = $this->createWorkflowStepTemplate();
        $this->applyInput($step, $input);

        $this->entityManager->persist($step);
        $this->entityManager->flush();

        $this->auditCreated($step);

        return $step;
    }

    public function update(WeldingWorkflowStepTemplateInterface $step, WeldingWorkflowStepTemplateInputInterface $input): void
    {
        $this->applyInput($step, $input);
        $this->entityManager->flush();

        $this->auditUpdated($step);
    }

    public function delete(WeldingWorkflowStepTemplateInterface $step): void
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
                    throw new RuntimeException(sprintf('WeldingWorkflowStepTemplate #%d not found', $stepId));
                }
                $step->setPosition($position);
            }

            $this->entityManager->flush();
        });

        $this->auditLogger->log('welding', 'workflow_step_template.reordered', 'WeldingWorkflowStepTemplate', null, [
            'orderedStepIds' => $orderedStepIds,
        ]);
    }

    protected function createWorkflowStepTemplate(): WeldingWorkflowStepTemplateInterface
    {
        return new WeldingWorkflowStepTemplate();
    }

    protected function applyInput(WeldingWorkflowStepTemplateInterface $step, WeldingWorkflowStepTemplateInputInterface $input): void
    {
        $workflowTemplateId = $input->getWorkflowTemplateId();
        if (null === $step->getWorkflowTemplate() && null !== $workflowTemplateId) {
            $workflowTemplate = $this->workflowTemplateRepository->find($workflowTemplateId);
            if (null === $workflowTemplate) {
                throw new RuntimeException(sprintf('WeldingWorkflowTemplate #%d not found', $workflowTemplateId));
            }
            $step->setWorkflowTemplate($workflowTemplate);
        }

        $step->setPosition($input->getPosition());
        $step->setTitle($input->getTitle());
        $step->setDescription($input->getDescription());
        $step->setRequiresValidation($input->isRequiresValidation());
        $step->setValidatorRole($input->isRequiresValidation() ? $input->getValidatorRole() : null);
    }

    protected function auditCreated(WeldingWorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.created', 'WeldingWorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditUpdated(WeldingWorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.updated', 'WeldingWorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditDeleted(WeldingWorkflowStepTemplateInterface $step): void
    {
        $this->auditLogger->log('welding', 'workflow_step_template.deleted', 'WeldingWorkflowStepTemplate', $step->getId(), $this->auditPayload($step));
    }

    protected function auditPayload(WeldingWorkflowStepTemplateInterface $step): array
    {
        return [
            'title' => $step->getTitle(),
            'position' => $step->getPosition(),
            'requiresValidation' => $step->getRequiresValidation(),
            'workflowTemplateId' => $step->getWorkflowTemplate()?->getId(),
        ];
    }
}
