<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WorkflowStepStatusEnum;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Dto\WorkflowStepValidationInput;
use Aurora\Module\Welding\WorkflowStep\Dto\WorkflowStepValidationInputInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepManagerInterface::class)]
class WorkflowStepManager implements WorkflowStepManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function submit(WorkflowStepInterface $step, CoreUserInterface $welder): void
    {
        $allowedFrom = [WorkflowStepStatusEnum::Pending, WorkflowStepStatusEnum::InProgress];
        if (!in_array($step->getStatus(), $allowedFrom, true)) {
            throw new RuntimeException(sprintf('Step #%d cannot be submitted from status %s', $step->getId(), $step->getStatus()->value));
        }

        $step->setCompletedBy($welder);
        $step->setCompletedAt(new DateTimeImmutable());
        $step->setRejectionComment(null);

        $requiresValidation = $step->getStepTemplate()?->getRequiresValidation() ?? false;
        if ($requiresValidation) {
            $step->setStatus(WorkflowStepStatusEnum::AwaitingValidation);
            $this->syncWorkflowAwaitingValidation($step->getWorkflow());
        } else {
            $step->setStatus(WorkflowStepStatusEnum::Validated);
            $step->setValidatedAt(new DateTimeImmutable());
            $step->setValidatedBy($welder);
        }

        $this->entityManager->flush();

        $this->maybeCompleteWorkflow($step->getWorkflow());

        $this->auditLogger->log('welding', 'workflow_step.submitted', 'WorkflowStep', $step->getId(), $this->auditPayload($step));
    }

    public function recordValidation(WorkflowStepInterface $step, CoreUserInterface $validator, WorkflowStepValidationInputInterface $input): void
    {
        if (WorkflowStepStatusEnum::AwaitingValidation !== $step->getStatus()) {
            throw new RuntimeException(sprintf('Step #%d is not awaiting validation (current: %s)', $step->getId(), $step->getStatus()->value));
        }

        if (WorkflowStepValidationInput::DECISION_VALIDATE === $input->getDecision()) {
            $step->setStatus(WorkflowStepStatusEnum::Validated);
            $step->setValidatedBy($validator);
            $step->setValidatedAt(new DateTimeImmutable());
            $step->setValidationComment($input->getComment());

            $this->entityManager->flush();

            $this->syncWorkflowAwaitingValidation($step->getWorkflow());
            $this->maybeCompleteWorkflow($step->getWorkflow());

            $this->auditLogger->log('welding', 'workflow_step.validated', 'WorkflowStep', $step->getId(), $this->auditPayload($step));

            return;
        }

        // Decision = reject → welder must redo
        $step->setStatus(WorkflowStepStatusEnum::Pending);
        $step->setRejectionComment($input->getComment());
        $step->setCompletedBy(null);
        $step->setCompletedAt(null);
        $step->setValidatedBy(null);
        $step->setValidatedAt(null);
        $step->setValidationComment(null);

        $this->entityManager->flush();

        $this->syncWorkflowAwaitingValidation($step->getWorkflow());

        $this->auditLogger->log('welding', 'workflow_step.rejected', 'WorkflowStep', $step->getId(), [
            ...$this->auditPayload($step),
            'rejectionComment' => $input->getComment(),
            'rejectedBy' => $validator->getId(),
        ]);
    }

    /**
     * If every step is Validated, mark the workflow Completed. Idempotent.
     */
    protected function maybeCompleteWorkflow(?WorkflowInterface $workflow): void
    {
        if (null === $workflow || WorkflowStatusEnum::Completed === $workflow->getStatus()) {
            return;
        }

        foreach ($workflow->getSteps() as $step) {
            if (WorkflowStepStatusEnum::Validated !== $step->getStatus()) {
                return;
            }
        }

        $workflow->setStatus(WorkflowStatusEnum::Completed);
        $workflow->setCompletedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.completed', 'Workflow', $workflow->getId(), [
            'reference' => $workflow->getReference(),
            'completedAt' => $workflow->getCompletedAt()?->format(\DATE_ATOM),
        ]);
    }

    /**
     * Reflects step-level AwaitingValidation on the workflow status. If any
     * step is awaiting validation, the workflow is too; otherwise it stays
     * (or returns to) InProgress.
     */
    protected function syncWorkflowAwaitingValidation(?WorkflowInterface $workflow): void
    {
        if (null === $workflow || $workflow->getStatus()->isTerminal()) {
            return;
        }

        $anyAwaiting = false;
        foreach ($workflow->getSteps() as $step) {
            if (WorkflowStepStatusEnum::AwaitingValidation === $step->getStatus()) {
                $anyAwaiting = true;
                break;
            }
        }

        $target = $anyAwaiting ? WorkflowStatusEnum::AwaitingValidation : WorkflowStatusEnum::InProgress;
        if ($workflow->getStatus() !== $target) {
            $workflow->setStatus($target);
            $this->entityManager->flush();
        }
    }

    protected function auditPayload(WorkflowStepInterface $step): array
    {
        return [
            'workflowId' => $step->getWorkflow()?->getId(),
            'position' => $step->getPosition(),
            'status' => $step->getStatus()->value,
            'completedBy' => $step->getCompletedBy()?->getId(),
            'validatedBy' => $step->getValidatedBy()?->getId(),
        ];
    }
}
