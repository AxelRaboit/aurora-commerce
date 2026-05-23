<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WeldingWorkflowStepPdfTemplateInput implements WeldingWorkflowStepPdfTemplateInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'backend.welding.workflow_step_pdf_templates.errors.step_required')]
        public readonly ?int $workflowStepTemplateId = null,
        #[Assert\NotNull(message: 'backend.welding.workflow_step_pdf_templates.errors.pdf_template_required')]
        public readonly ?int $pdfTemplateId = null,
        #[Assert\PositiveOrZero(message: 'backend.welding.workflow_step_pdf_templates.errors.position_invalid')]
        public readonly int $position = 0,
        public readonly bool $required = true,
    ) {}

    public function getWorkflowStepTemplateId(): ?int
    {
        return $this->workflowStepTemplateId;
    }

    public function getPdfTemplateId(): ?int
    {
        return $this->pdfTemplateId;
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
