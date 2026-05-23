<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTemplateInputFactoryInterface::class)]
class WeldingWorkflowStepTemplateInputFactory implements WeldingWorkflowStepTemplateInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowStepTemplateInputInterface
    {
        $workflowTemplateId = $data['workflowTemplateId'] ?? null;
        $validatorRoleValue = $data['validatorRole'] ?? null;

        return new WeldingWorkflowStepTemplateInput(
            workflowTemplateId: null === $workflowTemplateId ? null : (int) $workflowTemplateId,
            position: (int) ($data['position'] ?? 0),
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            requiresValidation: (bool) ($data['requiresValidation'] ?? false),
            validatorRole: null === $validatorRoleValue ? null : WeldingValidatorRoleEnum::tryFrom((string) $validatorRoleValue),
        );
    }
}
