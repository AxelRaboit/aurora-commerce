<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepTemplateSerializerInterface::class)]
class WorkflowStepTemplateSerializer implements WorkflowStepTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowStepTemplateInterface $step): array
    {
        return [
            'id' => $step->getId(),
            'workflowTemplateId' => $step->getWorkflowTemplate()?->getId(),
            'position' => $step->getPosition(),
            'title' => $step->getTitle(),
            'description' => $step->getDescription(),
            'requiresValidation' => $step->getRequiresValidation(),
            'validatorRole' => $step->getValidatorRole()?->value,
            'pdfTemplatesCount' => $step->getPdfTemplates()->count(),
        ];
    }
}
