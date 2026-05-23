<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

interface WorkflowStepValidationInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WorkflowStepValidationInputInterface;
}
