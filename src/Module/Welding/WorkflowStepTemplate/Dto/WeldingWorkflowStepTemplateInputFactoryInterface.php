<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

interface WeldingWorkflowStepTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingWorkflowStepTemplateInputInterface;
}
