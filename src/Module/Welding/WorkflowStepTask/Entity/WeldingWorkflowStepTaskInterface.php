<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use DateTimeInterface;

interface WeldingWorkflowStepTaskInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowStep(): ?WeldingWorkflowStepInterface;

    public function setWorkflowStep(?WeldingWorkflowStepInterface $workflowStep): static;

    public function getTaskTemplate(): ?WeldingWorkflowStepTaskTemplateInterface;

    public function setTaskTemplate(?WeldingWorkflowStepTaskTemplateInterface $taskTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getRequired(): bool;

    public function setRequired(bool $required): static;

    public function getDone(): bool;

    public function setDone(bool $done): static;

    public function getDoneBy(): ?CoreUserInterface;

    public function setDoneBy(?CoreUserInterface $doneBy): static;

    public function getDoneAt(): ?DateTimeInterface;

    public function setDoneAt(?DateTimeInterface $doneAt): static;
}
