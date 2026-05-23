<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepValidationInputFactoryInterface::class)]
class WorkflowStepValidationInputFactory implements WorkflowStepValidationInputFactoryInterface
{
    public function fromArray(array $data): WorkflowStepValidationInputInterface
    {
        return new WorkflowStepValidationInput(
            decision: Str::trimFromArray($data, 'decision', WorkflowStepValidationInput::DECISION_VALIDATE),
            comment: Str::trimOrNullFromArray($data, 'comment'),
        );
    }
}
