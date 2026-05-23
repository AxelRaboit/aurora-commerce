<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto;

interface WeldingWorkflowStepTaskTemplateInputInterface
{
    public function getWorkflowStepTemplateId(): ?int;

    public function getLabel(): string;

    public function getDescription(): ?string;

    public function getPosition(): int;

    public function isRequired(): bool;
}
