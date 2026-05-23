<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;

interface WeldingWorkflowStepTaskTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowStepTemplate(): ?WeldingWorkflowStepTemplateInterface;

    public function setWorkflowStepTemplate(?WeldingWorkflowStepTemplateInterface $workflowStepTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getRequired(): bool;

    public function setRequired(bool $required): static;
}
