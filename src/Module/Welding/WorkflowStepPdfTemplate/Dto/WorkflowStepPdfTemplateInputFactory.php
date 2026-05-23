<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepPdfTemplateInputFactoryInterface::class)]
class WorkflowStepPdfTemplateInputFactory implements WorkflowStepPdfTemplateInputFactoryInterface
{
    public function fromArray(array $data): WorkflowStepPdfTemplateInputInterface
    {
        $stepId = $data['workflowStepTemplateId'] ?? null;
        $pdfId = $data['pdfTemplateId'] ?? null;

        return new WorkflowStepPdfTemplateInput(
            workflowStepTemplateId: null === $stepId ? null : (int) $stepId,
            pdfTemplateId: null === $pdfId ? null : (int) $pdfId,
            position: (int) ($data['position'] ?? 0),
            required: (bool) ($data['required'] ?? true),
        );
    }
}
