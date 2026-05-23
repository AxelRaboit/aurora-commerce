<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Dto;

interface WeldingWorkflowTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingWorkflowTemplateInputInterface;
}
