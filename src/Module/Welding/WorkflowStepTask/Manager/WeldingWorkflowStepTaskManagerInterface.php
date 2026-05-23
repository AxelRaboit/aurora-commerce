<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;

interface WeldingWorkflowStepTaskManagerInterface
{
    /**
     * Snapshot template tasks → instance tasks for a fresh step at workflow start.
     * Returns the created instance list (already persisted but not flushed —
     * the caller controls the flush, mirroring snapshotSteps()).
     *
     * @param iterable<WeldingWorkflowStepTaskTemplateInterface> $templates
     *
     * @return list<WeldingWorkflowStepTaskInterface>
     */
    public function snapshotFromTemplates(WeldingWorkflowStepInterface $step, iterable $templates): array;

    /**
     * Flip a task's done state, recording who did it and when. Persists + flushes.
     */
    public function setDone(WeldingWorkflowStepTaskInterface $task, bool $done, CoreUserInterface $actor): void;
}
