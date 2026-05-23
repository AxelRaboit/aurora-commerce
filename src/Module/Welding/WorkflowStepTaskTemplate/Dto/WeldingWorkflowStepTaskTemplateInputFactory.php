<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTaskTemplateInputFactoryInterface::class)]
class WeldingWorkflowStepTaskTemplateInputFactory implements WeldingWorkflowStepTaskTemplateInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowStepTaskTemplateInputInterface
    {
        $stepId = $data['workflowStepTemplateId'] ?? null;
        $description = $data['description'] ?? null;

        return new WeldingWorkflowStepTaskTemplateInput(
            workflowStepTemplateId: null === $stepId ? null : (int) $stepId,
            label: mb_trim((string) ($data['label'] ?? '')),
            description: null === $description ? null : mb_trim((string) $description),
            position: (int) ($data['position'] ?? 0),
            required: (bool) ($data['required'] ?? true),
        );
    }
}
