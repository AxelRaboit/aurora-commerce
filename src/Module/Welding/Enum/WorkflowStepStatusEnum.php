<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WorkflowStepStatusEnum: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case AwaitingValidation = 'awaiting_validation';
    case Validated = 'validated';
    case Rejected = 'rejected';

    public function getLabelKey(): string
    {
        return 'backend.welding.workflow_steps.status_'.$this->value;
    }
}
