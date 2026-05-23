<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;

interface WeldingWorkflowStepTemplateInputInterface
{
    public function getWorkflowTemplateId(): ?int;

    public function getPosition(): int;

    public function getTitle(): string;

    public function getDescription(): ?string;

    public function isRequiresValidation(): bool;

    public function getValidatorRole(): ?WeldingValidatorRoleEnum;
}
