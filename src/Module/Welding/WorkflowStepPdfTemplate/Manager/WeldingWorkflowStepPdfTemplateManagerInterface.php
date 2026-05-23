<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WeldingWorkflowStepPdfTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface;

interface WeldingWorkflowStepPdfTemplateManagerInterface
{
    public function create(WeldingWorkflowStepPdfTemplateInputInterface $input): WeldingWorkflowStepPdfTemplateInterface;

    public function update(WeldingWorkflowStepPdfTemplateInterface $entry, WeldingWorkflowStepPdfTemplateInputInterface $input): void;

    public function delete(WeldingWorkflowStepPdfTemplateInterface $entry): void;
}
