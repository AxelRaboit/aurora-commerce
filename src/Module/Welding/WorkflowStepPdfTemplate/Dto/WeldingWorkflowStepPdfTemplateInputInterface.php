<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto;

interface WeldingWorkflowStepPdfTemplateInputInterface
{
    public function getWorkflowStepTemplateId(): ?int;

    public function getPdfTemplateId(): ?int;

    public function getPosition(): int;

    public function isRequired(): bool;
}
