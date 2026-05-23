<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WeldingWorkflowStepTaskTemplateInput implements WeldingWorkflowStepTaskTemplateInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'backend.welding.workflow_step_tasks.errors.step_required')]
        public readonly ?int $workflowStepTemplateId = null,
        #[Assert\NotBlank(message: 'backend.welding.workflow_step_tasks.errors.label_required')]
        #[Assert\Length(max: 300, maxMessage: 'backend.welding.workflow_step_tasks.errors.label_too_long')]
        public readonly string $label = '',
        public readonly ?string $description = null,
        #[Assert\PositiveOrZero(message: 'backend.welding.workflow_step_tasks.errors.position_invalid')]
        public readonly int $position = 0,
        public readonly bool $required = true,
    ) {}

    public function getWorkflowStepTemplateId(): ?int
    {
        return $this->workflowStepTemplateId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
