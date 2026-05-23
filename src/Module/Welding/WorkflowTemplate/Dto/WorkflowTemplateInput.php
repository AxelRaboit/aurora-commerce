<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WorkflowTemplateInput implements WorkflowTemplateInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.welding.workflow_templates.errors.title_required')]
        #[Assert\Length(max: 200, maxMessage: 'backend.welding.workflow_templates.errors.title_too_long')]
        public readonly string $title = '',
        public readonly ?string $description = null,
        #[Assert\Length(max: 100, maxMessage: 'backend.welding.workflow_templates.errors.applicable_to_too_long')]
        public readonly ?string $applicableTo = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getApplicableTo(): ?string
    {
        return $this->applicableTo;
    }
}
