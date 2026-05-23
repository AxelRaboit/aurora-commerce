<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingWorkflowStatusEnum: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case AwaitingValidation = 'awaiting_validation';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.welding.workflows.status_'.$this->value;
    }

    public function isTerminal(): bool
    {
        return self::Completed === $this || self::Rejected === $this || self::Archived === $this;
    }
}
