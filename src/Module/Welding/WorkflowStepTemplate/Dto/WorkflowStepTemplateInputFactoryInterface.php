<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

interface WorkflowStepTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WorkflowStepTemplateInputInterface;
}
