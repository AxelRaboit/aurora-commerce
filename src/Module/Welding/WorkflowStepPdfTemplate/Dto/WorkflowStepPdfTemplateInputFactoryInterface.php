<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto;

interface WorkflowStepPdfTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WorkflowStepPdfTemplateInputInterface;
}
