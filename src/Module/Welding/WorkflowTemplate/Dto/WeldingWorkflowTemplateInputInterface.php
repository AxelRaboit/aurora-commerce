<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Dto;

interface WeldingWorkflowTemplateInputInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getApplicableTo(): ?string;
}
