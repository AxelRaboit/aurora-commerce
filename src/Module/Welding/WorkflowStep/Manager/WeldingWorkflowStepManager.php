<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WeldingWorkflowStepStatusEnum;
use Aurora\Module\Welding\Service\WeldingStepNotifier;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Dto\WeldingWorkflowStepValidationInput;
use Aurora\Module\Welding\WorkflowStep\Dto\WeldingWorkflowStepValidationInputInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepManagerInterface::class)]
class WeldingWorkflowStepManager implements WeldingWorkflowStepManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly WeldingStepNotifier $notifier,
    ) {}

    public function submit(WeldingWorkflowStepInterface $step, CoreUserInterface $welder): void
    {
        $allowedFrom = [WeldingWorkflowStepStatusEnum::Pending, WeldingWorkflowStepStatusEnum::InProgress];
        if (!in_array($step->getStatus(), $allowedFrom, true)) {
            throw new RuntimeException(sprintf('Step #%d cannot be submitted from status %s', $step->getId(), $step->getStatus()->value));
        }

        $step->setCompletedBy($welder);
        $step->setCompletedAt(new DateTimeImmutable());
        $step->setRejectionComment(null);

        $requiresValidation = $step->getStepTemplate()?->getRequiresValidation() ?? false;
        if ($requiresValidation) {
            $step->setStatus(WeldingWorkflowStepStatusEnum::AwaitingValidation);
            $this->syncWorkflowAwaitingValidation($step->getWorkflow());
        } else {
            $step->setStatus(WeldingWorkflowStepStatusEnum::Validated);
            $step->setValidatedAt(new DateTimeImmutable());
            $step->setValidatedBy($welder);
        }

        $this->entityManager->flush();

        $this->maybeCompleteWorkflow($step->getWorkflow());

        $this->auditLogger->log('welding', 'workflow_step.submitted', 'WeldingWorkflowStep', $step->getId(), $this->auditPayload($step));

        if ($requiresValidation) {
            $this->notifier->notifyAwaitingValidation($step);
        }
    }

    public function recordValidation(WeldingWorkflowStepInterface $step, CoreUserInterface $validator, WeldingWorkflowStepValidationInputInterface $input): void
    {
        if (WeldingWorkflowStepStatusEnum::AwaitingValidation !== $step->getStatus()) {
            throw new RuntimeException(sprintf('Step #%d is not awaiting validation (current: %s)', $step->getId(), $step->getStatus()->value));
        }

        if (WeldingWorkflowStepValidationInput::DECISION_VALIDATE === $input->getDecision()) {
            $step->setStatus(WeldingWorkflowStepStatusEnum::Validated);
            $step->setValidatedBy($validator);
            $step->setValidatedAt(new DateTimeImmutable());
            $step->setValidationComment($input->getComment());

            $this->entityManager->flush();

            $this->syncWorkflowAwaitingValidation($step->getWorkflow());
            $this->maybeCompleteWorkflow($step->getWorkflow());

            $this->auditLogger->log('welding', 'workflow_step.validated', 'WeldingWorkflowStep', $step->getId(), $this->auditPayload($step));

            return;
        }

        // Decision = reject → welder must redo
        $step->setStatus(WeldingWorkflowStepStatusEnum::Pending);
        $step->setRejectionComment($input->getComment());
        $step->setCompletedBy(null);
        $step->setCompletedAt(null);
        $step->setValidatedBy(null);
        $step->setValidatedAt(null);
        $step->setValidationComment(null);

        $this->entityManager->flush();

        $this->syncWorkflowAwaitingValidation($step->getWorkflow());

        $this->auditLogger->log('welding', 'workflow_step.rejected', 'WeldingWorkflowStep', $step->getId(), [
            ...$this->auditPayload($step),
            'rejectionComment' => $input->getComment(),
            'rejectedBy' => $validator->getId(),
        ]);
    }

    /**
     * If every step is Validated, mark the workflow Completed. Idempotent.
     */
    protected function maybeCompleteWorkflow(?WeldingWorkflowInterface $workflow): void
    {
        if (null === $workflow || WeldingWorkflowStatusEnum::Completed === $workflow->getStatus()) {
            return;
        }

        foreach ($workflow->getSteps() as $step) {
            if (WeldingWorkflowStepStatusEnum::Validated !== $step->getStatus()) {
                return;
            }
        }

        $workflow->setStatus(WeldingWorkflowStatusEnum::Completed);
        $workflow->setCompletedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.completed', 'WeldingWorkflow', $workflow->getId(), [
            'reference' => $workflow->getReference(),
            'completedAt' => $workflow->getCompletedAt()?->format(\DATE_ATOM),
        ]);
    }

    /**
     * Reflects step-level AwaitingValidation on the workflow status. If any
     * step is awaiting validation, the workflow is too; otherwise it stays
     * (or returns to) InProgress.
     */
    protected function syncWorkflowAwaitingValidation(?WeldingWorkflowInterface $workflow): void
    {
        if (null === $workflow || $workflow->getStatus()->isTerminal()) {
            return;
        }

        $anyAwaiting = false;
        foreach ($workflow->getSteps() as $step) {
            if (WeldingWorkflowStepStatusEnum::AwaitingValidation === $step->getStatus()) {
                $anyAwaiting = true;
                break;
            }
        }

        $target = $anyAwaiting ? WeldingWorkflowStatusEnum::AwaitingValidation : WeldingWorkflowStatusEnum::InProgress;
        if ($workflow->getStatus() !== $target) {
            $workflow->setStatus($target);
            $this->entityManager->flush();
        }
    }

    protected function auditPayload(WeldingWorkflowStepInterface $step): array
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
