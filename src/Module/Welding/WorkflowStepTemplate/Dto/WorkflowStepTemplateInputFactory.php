<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepTemplateInputFactoryInterface::class)]
class WorkflowStepTemplateInputFactory implements WorkflowStepTemplateInputFactoryInterface
{
    public function fromArray(array $data): WorkflowStepTemplateInputInterface
    {
        $workflowTemplateId = $data['workflowTemplateId'] ?? null;
        $validatorRoleValue = $data['validatorRole'] ?? null;

        return new WorkflowStepTemplateInput(
            workflowTemplateId: null === $workflowTemplateId ? null : (int) $workflowTemplateId,
            position: (int) ($data['position'] ?? 0),
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            requiresValidation: (bool) ($data['requiresValidation'] ?? false),
            validatorRole: null === $validatorRoleValue ? null : WeldingValidatorRoleEnum::tryFrom((string) $validatorRoleValue),
        );
    }
}
