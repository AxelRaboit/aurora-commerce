<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Manager;

use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WeldingWorkflowStepTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;

interface WeldingWorkflowStepTemplateManagerInterface
{
    public function create(WeldingWorkflowStepTemplateInputInterface $input): WeldingWorkflowStepTemplateInterface;

    public function update(WeldingWorkflowStepTemplateInterface $step, WeldingWorkflowStepTemplateInputInterface $input): void;

    public function delete(WeldingWorkflowStepTemplateInterface $step): void;

    /**
     * Re-assigns positions for the given step ids in the order provided. Atomic:
     * either every step is renumbered or nothing changes. Step ids not in the
     * list are left untouched.
     *
     * @param int[] $orderedStepIds step ids in the order they should appear (0-indexed positions)
     */
    public function reorder(array $orderedStepIds): void;
}
