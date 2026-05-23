<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Manager;

use Aurora\Module\Welding\WorkflowTemplate\Dto\WorkflowTemplateInputInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;

interface WorkflowTemplateManagerInterface
{
    public function create(WorkflowTemplateInputInterface $input): WorkflowTemplateInterface;

    public function update(WorkflowTemplateInterface $workflowTemplate, WorkflowTemplateInputInterface $input): void;

    public function delete(WorkflowTemplateInterface $workflowTemplate): void;

    public function publish(WorkflowTemplateInterface $workflowTemplate): void;

    public function archive(WorkflowTemplateInterface $workflowTemplate): void;

    /**
     * Clones a published template into a new Draft with the next version number,
     * preserving the parent-version link for audit trail. Steps + step-pdf joins
     * are NOT deep-copied here — the admin starts from a clean slate (or runs a
     * separate clone-with-steps action later if useful).
     */
    public function cloneAsNewVersion(WorkflowTemplateInterface $workflowTemplate): WorkflowTemplateInterface;
}
