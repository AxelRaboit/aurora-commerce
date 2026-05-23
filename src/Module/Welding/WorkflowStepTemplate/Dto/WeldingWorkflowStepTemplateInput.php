<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Symfony\Component\Validator\Constraints as Assert;

class WeldingWorkflowStepTemplateInput implements WeldingWorkflowStepTemplateInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'backend.welding.workflow_step_templates.errors.workflow_template_required')]
        public readonly ?int $workflowTemplateId = null,
        #[Assert\PositiveOrZero(message: 'backend.welding.workflow_step_templates.errors.position_invalid')]
        public readonly int $position = 0,
        #[Assert\NotBlank(message: 'backend.welding.workflow_step_templates.errors.title_required')]
        #[Assert\Length(max: 200, maxMessage: 'backend.welding.workflow_step_templates.errors.title_too_long')]
        public readonly string $title = '',
        public readonly ?string $description = null,
        public readonly bool $requiresValidation = false,
        public readonly ?WeldingValidatorRoleEnum $validatorRole = null,
    ) {}

    public function getWorkflowTemplateId(): ?int
    {
        return $this->workflowTemplateId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isRequiresValidation(): bool
    {
        return $this->requiresValidation;
    }

    public function getValidatorRole(): ?WeldingValidatorRoleEnum
    {
        return $this->validatorRole;
    }
}
