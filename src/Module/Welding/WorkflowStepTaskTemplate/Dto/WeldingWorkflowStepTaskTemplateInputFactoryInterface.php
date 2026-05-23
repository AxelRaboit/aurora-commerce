<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto;

interface WeldingWorkflowStepTaskTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingWorkflowStepTaskTemplateInputInterface;
}
