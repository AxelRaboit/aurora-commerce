<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepValidationInputFactoryInterface::class)]
class WeldingWorkflowStepValidationInputFactory implements WeldingWorkflowStepValidationInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowStepValidationInputInterface
    {
        return new WeldingWorkflowStepValidationInput(
            decision: Str::trimFromArray($data, 'decision', WeldingWorkflowStepValidationInput::DECISION_VALIDATE),
            comment: Str::trimOrNullFromArray($data, 'comment'),
        );
    }
}
