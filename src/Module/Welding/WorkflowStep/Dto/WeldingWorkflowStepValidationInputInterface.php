<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

interface WeldingWorkflowStepValidationInputInterface
{
    public function getDecision(): string;

    public function getComment(): ?string;
}
