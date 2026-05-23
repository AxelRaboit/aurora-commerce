<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WeldingWorkflowInput implements WeldingWorkflowInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'backend.welding.workflows.errors.template_required')]
        public readonly ?int $templateId = null,
        public readonly ?int $assigneeId = null,
        #[Assert\Length(max: 100)]
        public readonly ?string $contextType = null,
        public readonly ?int $contextId = null,
    ) {}

    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }

    public function getAssigneeId(): ?int
    {
        return $this->assigneeId;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }
}
