<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepPdfTemplateInputFactoryInterface::class)]
class WeldingWorkflowStepPdfTemplateInputFactory implements WeldingWorkflowStepPdfTemplateInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowStepPdfTemplateInputInterface
    {
        $stepId = $data['workflowStepTemplateId'] ?? null;
        $pdfId = $data['pdfTemplateId'] ?? null;

        return new WeldingWorkflowStepPdfTemplateInput(
            workflowStepTemplateId: null === $stepId ? null : (int) $stepId,
            pdfTemplateId: null === $pdfId ? null : (int) $pdfId,
            position: (int) ($data['position'] ?? 0),
            required: (bool) ($data['required'] ?? true),
        );
    }
}
