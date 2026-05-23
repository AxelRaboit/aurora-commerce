<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingWorkflowTemplateStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabelKey(): string
    {
        return 'backend.welding.workflow_templates.status_'.$this->value;
    }

    public function isEditable(): bool
    {
        return self::Draft === $this;
    }
}
