<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WeldingWorkflowStepStatusEnum;
use Aurora\Module\Welding\Setting\WeldingSettingEnum;
use Aurora\Module\Welding\Workflow\Dto\WeldingWorkflowInputInterface;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflow;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTask\Manager\WeldingWorkflowStepTaskManagerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowManagerInterface::class)]
class WeldingWorkflowManager implements WeldingWorkflowManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingWorkflowTemplateRepository $templateRepository,
        protected readonly EmployeeRepository $employeeRepository,
        protected readonly SettingRepository $settingRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly AuditLogger $auditLogger,
        protected readonly WeldingWorkflowStepTaskManagerInterface $taskManager,
    ) {}

    public function create(WeldingWorkflowInputInterface $input): WeldingWorkflowInterface
    {
        $template = $this->templateRepository->find($input->getTemplateId());
        if (null === $template) {
            throw new RuntimeException(sprintf('WeldingWorkflowTemplate #%d not found', $input->getTemplateId()));
        }

        $workflow = $this->createWorkflow();
        $workflow->setTemplate($template);
        $workflow->setReference($this->generateReference());
        $this->applyInput($workflow, $input);
        $workflow->setStatus(WeldingWorkflowStatusEnum::Draft);

        $this->entityManager->persist($workflow);
        $this->entityManager->flush();

        $this->auditCreated($workflow);

        return $workflow;
    }

    public function start(WeldingWorkflowInterface $workflow): void
    {
        if (WeldingWorkflowStatusEnum::Draft !== $workflow->getStatus()) {
            throw new RuntimeException(sprintf('WeldingWorkflow #%d cannot be started from status %s', $workflow->getId(), $workflow->getStatus()->value));
        }

        $template = $workflow->getTemplate();
        if (!$template instanceof WeldingWorkflowTemplateInterface) {
            throw new RuntimeException(sprintf('WeldingWorkflow #%d has no template', $workflow->getId()));
        }

        $this->snapshotSteps($workflow);
        $workflow->setStartedAt(new DateTimeImmutable());
        $workflow->setStatus(WeldingWorkflowStatusEnum::InProgress);

        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.started', 'WeldingWorkflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    public function reject(WeldingWorkflowInterface $workflow, string $reason): void
    {
        if ($workflow->getStatus()->isTerminal()) {
            throw new RuntimeException(sprintf('WeldingWorkflow #%d is already terminal (%s)', $workflow->getId(), $workflow->getStatus()->value));
        }

        $workflow->setStatus(WeldingWorkflowStatusEnum::Rejected);
        $workflow->setRejectedAt(new DateTimeImmutable());
        $workflow->setRejectionReason($reason);

        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.rejected', 'WeldingWorkflow', $workflow->getId(), [
            ...$this->auditPayload($workflow),
            'reason' => $reason,
        ]);
    }

    public function archive(WeldingWorkflowInterface $workflow): void
    {
        if (WeldingWorkflowStatusEnum::Completed !== $workflow->getStatus()) {
            throw new RuntimeException(sprintf('WeldingWorkflow #%d must be Completed to archive (current: %s)', $workflow->getId(), $workflow->getStatus()->value));
        }

        $workflow->setStatus(WeldingWorkflowStatusEnum::Archived);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow.archived', 'WeldingWorkflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    public function delete(WeldingWorkflowInterface $workflow): void
    {
        $this->auditDeleted($workflow);

        $this->entityManager->remove($workflow);
        $this->entityManager->flush();
    }

    protected function createWorkflow(): WeldingWorkflowInterface
    {
        return new WeldingWorkflow();
    }

    protected function createWorkflowStep(): WeldingWorkflowStepInterface
    {
        return new WeldingWorkflowStep();
    }

    /**
     * Hydrates the fields that may be set on BOTH create and update. The
     * template + reference are create-only (set inline in create()) and are
     * intentionally NOT touched here, so a client overriding applyInput()
     * for extra fields never accidentally rewrites the immutable identity.
     */
    protected function applyInput(WeldingWorkflowInterface $workflow, WeldingWorkflowInputInterface $input): void
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
     * Snapshots each WeldingWorkflowStepTemplate of the workflow's template into a
     * Pending WeldingWorkflowStep. Run once when the workflow is started — the steps
     * become the read-only execution scaffold for the welder. Subsequent
     * template version bumps don't affect running workflows.
     */
    protected function snapshotSteps(WeldingWorkflowInterface $workflow): void
    {
        $template = $workflow->getTemplate();
        if (!$template instanceof WeldingWorkflowTemplateInterface) {
            return;
        }

        foreach ($template->getSteps() as $stepTemplate) {
            $step = $this->createWorkflowStep();
            $step->setWorkflow($workflow);
            $step->setStepTemplate($stepTemplate);
            $step->setPosition($stepTemplate->getPosition());
            $step->setStatus(WeldingWorkflowStepStatusEnum::Pending);

            $this->entityManager->persist($step);

            $this->snapshotStepTasks($step, $stepTemplate);
        }
    }

    /**
     * Snapshots template tasks → instance tasks for one step at workflow start.
     * Delegates to the task manager which knows how to instantiate the concrete
     * class (so a client substituting WeldingWorkflowStepTask via
     * resolve_target_entities is honored).
     */
    protected function snapshotStepTasks(WeldingWorkflowStepInterface $step, WeldingWorkflowStepTemplateInterface $stepTemplate): void
    {
        $this->taskManager->snapshotFromTemplates($step, $stepTemplate->getTasks());
    }

    protected function generateReference(): string
    {
        $prefix = $this->settingRepository->getOrDefault(WeldingSettingEnum::ReferencePrefix);
        $year = (int) new DateTimeImmutable()->format('Y');

        return $this->sequenceGenerator->nextYearly($prefix, $year, 6);
    }

    protected function auditCreated(WeldingWorkflowInterface $workflow): void
    {
        $this->auditLogger->log('welding', 'workflow.created', 'WeldingWorkflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    protected function auditDeleted(WeldingWorkflowInterface $workflow): void
    {
        $this->auditLogger->log('welding', 'workflow.deleted', 'WeldingWorkflow', $workflow->getId(), $this->auditPayload($workflow));
    }

    protected function auditPayload(WeldingWorkflowInterface $workflow): array
    {
        return [
            'reference' => $workflow->getReference(),
            'templateId' => $workflow->getTemplate()?->getId(),
            'assigneeId' => $workflow->getAssignee()?->getId(),
            'status' => $workflow->getStatus()->value,
        ];
    }
}
