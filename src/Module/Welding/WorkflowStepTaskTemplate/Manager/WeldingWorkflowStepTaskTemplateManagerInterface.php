<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Manager;

use Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto\WeldingWorkflowStepTaskTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;

interface WeldingWorkflowStepTaskTemplateManagerInterface
{
    public function create(WeldingWorkflowStepTaskTemplateInputInterface $input): WeldingWorkflowStepTaskTemplateInterface;

    public function update(WeldingWorkflowStepTaskTemplateInterface $entry, WeldingWorkflowStepTaskTemplateInputInterface $input): void;

    public function delete(WeldingWorkflowStepTaskTemplateInterface $entry): void;

    /**
     * Reorder tasks within a single step template based on the given ordered id list.
     * Positions are reassigned starting from 0.
     *
     * @param list<int> $orderedIds
     */
    public function reorder(int $workflowStepTemplateId, array $orderedIds): void;
}
