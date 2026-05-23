<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepPdfTemplateSerializerInterface::class)]
class WorkflowStepPdfTemplateSerializer implements WorkflowStepPdfTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowStepPdfTemplateInterface $entry): array
    {
        $pdfTemplate = $entry->getPdfTemplate();

        return [
            'id' => $entry->getId(),
            'workflowStepTemplateId' => $entry->getWorkflowStepTemplate()?->getId(),
            'pdfTemplateId' => $pdfTemplate?->getId(),
            'pdfTemplateName' => $pdfTemplate?->getName(),
            'position' => $entry->getPosition(),
            'required' => $entry->getRequired(),
        ];
    }
}
