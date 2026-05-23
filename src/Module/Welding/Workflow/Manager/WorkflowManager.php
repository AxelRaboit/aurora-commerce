<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Welding\Enum\WorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WorkflowStepStatusEnum;
use Aurora\Module\Welding\Setting\WeldingSettingEnum;
use Aurora\Module\Welding\Workflow\Dto\WorkflowInputInterface;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowManagerInterface::class)]
class WorkflowManager implements WorkflowManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WorkflowTemplateRepository $templateRepository,
        protected readonly EmployeeRepository $employeeRepository,
        protected readonly SettingRepository $settingRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WorkflowInputInterface $input): WorkflowInterface
    {
        $template = $this->templateRepository->find($input->getTemplateId());
        if (null === $template) {
            throw new RuntimeException(sprintf('WorkflowTemplate #%d not found', $input->getTemplateId()));
        }

        $workflow = $this->createWorkflow();
        $workflow->setTemplate($template);
        $workflow->setReference($this->generateReference());
        $this->applyInput($workflow, $input);
        $workflow->setStatus(WorkflowStatusEnum::Draft);

        $this->entityManager->persist($workflow);
        $this->entityManager->flush();

        $this->auditCreated($workflow);

        return $workflow;
    }

    public function start(WorkflowInterface $workflow): void
    {
        if (WorkflowStatusEnum::Draft !== $workflow->getStatus()) {
            throw new RuntimeException(sprintf('Workflow #%d cannot be started from status %s', $workflow->getId(), $workflow->getStatus()->value));
        }

        $template = $workflow->getTemplate();
        if (null === $template) {
            throw new RuntimeException(sprintf('Workflow #%d has no template', $workflow->getId()));
        }

        $this->snapshotSteps($workflow);
        $workflow->setStartedAt(new DateTimeImmutable());
        $workflow->setStatus(WorkflowStatusEnum::InProgress);

        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.started', 'Workflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    public function reject(WorkflowInterface $workflow, string $reason): void
    {
        if ($workflow->getStatus()->isTerminal()) {
            throw new RuntimeException(sprintf('Workflow #%d is already terminal (%s)', $workflow->getId(), $workflow->getStatus()->value));
        }

        $workflow->setStatus(WorkflowStatusEnum::Rejected);
        $workflow->setRejectedAt(new DateTimeImmutable());
        $workflow->setRejectionReason($reason);

        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.rejected', 'Workflow', $workflow->getId(), [
            ...$this->auditPayload($workflow),
            'reason' => $reason,
        ]);
    }

    public function archive(WorkflowInterface $workflow): void
    {
        if (WorkflowStatusEnum::Completed !== $workflow->getStatus()) {
            throw new RuntimeException(sprintf('Workflow #%d must be Completed to archive (current: %s)', $workflow->getId(), $workflow->getStatus()->value));
        }

        $workflow->setStatus(WorkflowStatusEnum::Archived);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.archived', 'Workflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    public function delete(WorkflowInterface $workflow): void
    {
        $this->auditDeleted($workflow);

        $this->entityManager->remove($workflow);
        $this->entityManager->flush();
    }

    protected function createWorkflow(): WorkflowInterface
    {
        return new Workflow();
    }

    protected function createWorkflowStep(): WorkflowStepInterface
    {
        return new WorkflowStep();
    }

    /**
     * Hydrates the fields that may be set on BOTH create and update. The
     * template + reference are create-only (set inline in create()) and are
     * intentionally NOT touched here, so a client overriding applyInput()
     * for extra fields never accidentally rewrites the immutable identity.
     */
    protected function applyInput(WorkflowInterface $workflow, WorkflowInputInterface $input): void
    {
        if (null !== $input->getAssigneeId()) {
            $assignee = $this->employeeRepository->find($input->getAssigneeId());
            if (null === $assignee) {
                throw new RuntimeException(sprintf('Employee #%d not found', $input->getAssigneeId()));
            }
            $workflow->setAssignee($assignee);
        }

        if (null !== $input->getContextType()) {
            $workflow->setContextType($input->getContextType());
            $workflow->setContextId($input->getContextId());
        }
    }

    /**
     * Snapshots each WorkflowStepTemplate of the workflow's template into a
     * Pending WorkflowStep. Run once when the workflow is started — the steps
     * become the read-only execution scaffold for the welder. Subsequent
     * template version bumps don't affect running workflows.
     */
    protected function snapshotSteps(WorkflowInterface $workflow): void
    {
        $template = $workflow->getTemplate();
        if (null === $template) {
            return;
        }

        foreach ($template->getSteps() as $stepTemplate) {
            $step = $this->createWorkflowStep();
            $step->setWorkflow($workflow);
            $step->setStepTemplate($stepTemplate);
            $step->setPosition($stepTemplate->getPosition());
            $step->setStatus(WorkflowStepStatusEnum::Pending);

            $this->entityManager->persist($step);
        }
    }

    protected function generateReference(): string
    {
        $prefix = $this->settingRepository->getOrDefault(WeldingSettingEnum::ReferencePrefix);
        $year = (int) (new DateTimeImmutable())->format('Y');

        return $this->sequenceGenerator->nextYearly($prefix, $year, 6);
    }

    protected function auditCreated(WorkflowInterface $workflow): void
    {
        $this->auditLogger->log('welding', 'workflow.created', 'Workflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    protected function auditDeleted(WorkflowInterface $workflow): void
    {
        $this->auditLogger->log('welding', 'workflow.deleted', 'Workflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    protected function auditPayload(WorkflowInterface $workflow): array
    {
        return [
            'reference' => $workflow->getReference(),
            'templateId' => $workflow->getTemplate()?->getId(),
            'assigneeId' => $workflow->getAssignee()?->getId(),
            'status' => $workflow->getStatus()->value,
        ];
    }
}
