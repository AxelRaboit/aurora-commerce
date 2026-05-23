<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Serializer;

use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(WeldingWorkflowStepSerializerInterface::class)]
class WeldingWorkflowStepSerializer implements WeldingWorkflowStepSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepInterface $step): array
    {
        $template = $step->getStepTemplate();

        return [
            'id' => $step->getId(),
            'workflowId' => $step->getWorkflow()?->getId(),
            'stepTemplateId' => $template?->getId(),
            'title' => $template?->getTitle(),
            'description' => $template?->getDescription(),
            'requiresValidation' => $template?->getRequiresValidation() ?? false,
            'validatorRole' => $template?->getValidatorRole()?->value,
            'position' => $step->getPosition(),
            'status' => $step->getStatus()->value,
            'completedById' => $step->getCompletedBy()?->getId(),
            'completedAt' => $step->getCompletedAt()?->format(DATE_ATOM),
            'validatedById' => $step->getValidatedBy()?->getId(),
            'validatedAt' => $step->getValidatedAt()?->format(DATE_ATOM),
            'validationComment' => $step->getValidationComment(),
            'rejectionComment' => $step->getRejectionComment(),
        ];
    }
}
