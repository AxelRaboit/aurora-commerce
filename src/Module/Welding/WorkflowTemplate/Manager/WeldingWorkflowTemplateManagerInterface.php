<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Manager;

use Aurora\Module\Welding\WorkflowTemplate\Dto\WeldingWorkflowTemplateInputInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;

interface WeldingWorkflowTemplateManagerInterface
{
    public function create(WeldingWorkflowTemplateInputInterface $input): WeldingWorkflowTemplateInterface;

    public function update(WeldingWorkflowTemplateInterface $workflowTemplate, WeldingWorkflowTemplateInputInterface $input): void;

    public function delete(WeldingWorkflowTemplateInterface $workflowTemplate): void;

    public function publish(WeldingWorkflowTemplateInterface $workflowTemplate): void;

    public function archive(WeldingWorkflowTemplateInterface $workflowTemplate): void;

    /**
     * Clones a published template into a new Draft with the next version number,
     * preserving the parent-version link for audit trail. Steps + step-pdf joins
     * are NOT deep-copied here — the admin starts from a clean slate (or runs a
     * separate clone-with-steps action later if useful).
     */
    public function cloneAsNewVersion(WeldingWorkflowTemplateInterface $workflowTemplate): WeldingWorkflowTemplateInterface;
}
