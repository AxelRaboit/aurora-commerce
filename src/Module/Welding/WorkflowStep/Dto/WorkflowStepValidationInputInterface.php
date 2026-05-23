<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

interface WorkflowStepValidationInputInterface
{
    public function getDecision(): string;

    public function getComment(): ?string;
}
