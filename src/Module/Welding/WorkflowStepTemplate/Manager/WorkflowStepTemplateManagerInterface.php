<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Manager;

use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WorkflowStepTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;

interface WorkflowStepTemplateManagerInterface
{
    public function create(WorkflowStepTemplateInputInterface $input): WorkflowStepTemplateInterface;

    public function update(WorkflowStepTemplateInterface $step, WorkflowStepTemplateInputInterface $input): void;

    public function delete(WorkflowStepTemplateInterface $step): void;
}
