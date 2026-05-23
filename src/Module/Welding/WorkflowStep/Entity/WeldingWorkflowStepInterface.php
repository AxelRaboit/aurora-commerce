<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowStepStatusEnum;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

interface WeldingWorkflowStepInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflow(): ?WeldingWorkflowInterface;

    public function setWorkflow(?WeldingWorkflowInterface $workflow): static;

    public function getStepTemplate(): ?WeldingWorkflowStepTemplateInterface;

    public function setStepTemplate(?WeldingWorkflowStepTemplateInterface $stepTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getStatus(): WeldingWorkflowStepStatusEnum;

    public function setStatus(WeldingWorkflowStepStatusEnum $status): static;

    public function getCompletedBy(): ?CoreUserInterface;

    public function setCompletedBy(?CoreUserInterface $completedBy): static;

    public function getCompletedAt(): ?DateTimeInterface;

    public function setCompletedAt(?DateTimeInterface $completedAt): static;

    public function getValidatedBy(): ?CoreUserInterface;

    public function setValidatedBy(?CoreUserInterface $validatedBy): static;

    public function getValidatedAt(): ?DateTimeInterface;

    public function setValidatedAt(?DateTimeInterface $validatedAt): static;

    public function getValidationComment(): ?string;

    public function setValidationComment(?string $validationComment): static;

    public function getRejectionComment(): ?string;

    public function setRejectionComment(?string $rejectionComment): static;

    /** @return Collection<int, WeldingWorkflowStepTaskInterface> */
    public function getTasks(): Collection;
}
