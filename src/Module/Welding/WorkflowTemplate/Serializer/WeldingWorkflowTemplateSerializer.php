<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Serializer;

use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(WeldingWorkflowTemplateSerializerInterface::class)]
class WeldingWorkflowTemplateSerializer implements WeldingWorkflowTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowTemplateInterface $workflowTemplate): array
    {
        return [
            'id' => $workflowTemplate->getId(),
            'title' => $workflowTemplate->getTitle(),
            'description' => $workflowTemplate->getDescription(),
            'applicableTo' => $workflowTemplate->getApplicableTo(),
            'version' => $workflowTemplate->getVersion(),
            'status' => $workflowTemplate->getStatus()->value,
            'parentVersionId' => $workflowTemplate->getParentVersion()?->getId(),
            'stepsCount' => $workflowTemplate->getSteps()->count(),
            'createdAt' => $workflowTemplate->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
