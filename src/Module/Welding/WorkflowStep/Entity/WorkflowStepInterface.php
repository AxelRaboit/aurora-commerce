<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WorkflowStepStatusEnum;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use DateTimeInterface;

interface WorkflowStepInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflow(): ?WorkflowInterface;

    public function setWorkflow(?WorkflowInterface $workflow): static;

    public function getStepTemplate(): ?WorkflowStepTemplateInterface;

    public function setStepTemplate(?WorkflowStepTemplateInterface $stepTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getStatus(): WorkflowStepStatusEnum;

    public function setStatus(WorkflowStepStatusEnum $status): static;

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
}
