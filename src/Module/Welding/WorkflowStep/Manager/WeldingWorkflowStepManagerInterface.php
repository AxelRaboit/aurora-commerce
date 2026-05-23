<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Dto\WeldingWorkflowStepValidationInputInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;

interface WeldingWorkflowStepManagerInterface
{
    /**
     * Welder marks a step as submitted: Pending → AwaitingValidation (if the
     * template requires validation) or → Validated (otherwise).
     */
    public function submit(WeldingWorkflowStepInterface $step, CoreUserInterface $welder): void;

    /**
     * Validator records their decision. AwaitingValidation → Validated or
     * → Pending again (with rejectionComment) when decision = reject.
     */
    public function recordValidation(WeldingWorkflowStepInterface $step, CoreUserInterface $validator, WeldingWorkflowStepValidationInputInterface $input): void;
}
