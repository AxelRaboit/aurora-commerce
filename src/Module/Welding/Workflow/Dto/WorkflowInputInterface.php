<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Dto;

interface WorkflowInputInterface
{
    public function getTemplateId(): ?int;

    public function getAssigneeId(): ?int;

    public function getContextType(): ?string;

    public function getContextId(): ?int;
}
