<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

interface WeldingWorkflowInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getTemplate(): ?WeldingWorkflowTemplateInterface;

    public function setTemplate(?WeldingWorkflowTemplateInterface $template): static;

    public function getAssignee(): ?EmployeeInterface;

    public function setAssignee(?EmployeeInterface $assignee): static;

    public function getStatus(): WeldingWorkflowStatusEnum;

    public function setStatus(WeldingWorkflowStatusEnum $status): static;

    public function getStartedAt(): ?DateTimeInterface;

    public function setStartedAt(?DateTimeInterface $startedAt): static;

    public function getCompletedAt(): ?DateTimeInterface;

    public function setCompletedAt(?DateTimeInterface $completedAt): static;

    public function getRejectedAt(): ?DateTimeInterface;

    public function setRejectedAt(?DateTimeInterface $rejectedAt): static;

    public function getRejectionReason(): ?string;

    public function setRejectionReason(?string $rejectionReason): static;

    public function getContextType(): ?string;

    public function setContextType(?string $contextType): static;

    public function getContextId(): ?int;

    public function setContextId(?int $contextId): static;

    /** @return Collection<int, \Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface> */
    public function getSteps(): Collection;
}
