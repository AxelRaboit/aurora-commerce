<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepTaskTemplateSerializerInterface::class)]
class WeldingWorkflowStepTaskTemplateSerializer implements WeldingWorkflowStepTaskTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepTaskTemplateInterface $entry): array
    {
        return [
            'id' => $entry->getId(),
            'workflowStepTemplateId' => $entry->getWorkflowStepTemplate()?->getId(),
            'label' => $entry->getLabel(),
            'description' => $entry->getDescription(),
            'position' => $entry->getPosition(),
            'required' => $entry->getRequired(),
        ];
    }
}
