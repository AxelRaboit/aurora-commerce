<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WorkflowStepPdfTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;

interface WorkflowStepPdfTemplateManagerInterface
{
    public function create(WorkflowStepPdfTemplateInputInterface $input): WorkflowStepPdfTemplateInterface;

    public function update(WorkflowStepPdfTemplateInterface $entry, WorkflowStepPdfTemplateInputInterface $input): void;

    public function delete(WorkflowStepPdfTemplateInterface $entry): void;
}
