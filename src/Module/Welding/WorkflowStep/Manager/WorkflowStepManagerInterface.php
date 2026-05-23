<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Dto\WorkflowStepValidationInputInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;

interface WorkflowStepManagerInterface
{
    /**
     * Welder marks a step as submitted: Pending → AwaitingValidation (if the
     * template requires validation) or → Validated (otherwise).
     */
    public function submit(WorkflowStepInterface $step, CoreUserInterface $welder): void;

    /**
     * Validator records their decision. AwaitingValidation → Validated or
     * → Pending again (with rejectionComment) when decision = reject.
     */
    public function recordValidation(WorkflowStepInterface $step, CoreUserInterface $validator, WorkflowStepValidationInputInterface $input): void;
}
