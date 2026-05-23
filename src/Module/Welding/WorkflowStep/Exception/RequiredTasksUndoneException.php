<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Exception;

use DomainException;

/**
 * Raised when a welder tries to submit a step that still has unchecked
 * required tasks. Caught by the controller and surfaced as a 422 with the
 * translation key the runner displays inline.
 */
final class RequiredTasksUndoneException extends DomainException
{
    public const string TRANSLATION_KEY = 'welding.workflow_steps.errors.required_tasks_undone';

    public function __construct(public readonly int $stepId)
    {
        parent::__construct(sprintf('Step #%d still has undone required tasks.', $stepId));
    }
}
