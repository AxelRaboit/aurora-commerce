<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Serializer;

use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(WorkflowSerializerInterface::class)]
class WorkflowSerializer implements WorkflowSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowInterface $workflow): array
    {
        $template = $workflow->getTemplate();
        $assignee = $workflow->getAssignee();

        return [
            'id' => $workflow->getId(),
            'reference' => $workflow->getReference(),
            'templateId' => $template?->getId(),
            'templateTitle' => $template?->getTitle(),
            'templateVersion' => $template?->getVersion(),
            'assigneeId' => $assignee?->getId(),
            'assigneeName' => null === $assignee ? null : trim($assignee->getFirstName().' '.$assignee->getLastName()),
            'status' => $workflow->getStatus()->value,
            'startedAt' => $workflow->getStartedAt()?->format(DATE_ATOM),
            'completedAt' => $workflow->getCompletedAt()?->format(DATE_ATOM),
            'rejectedAt' => $workflow->getRejectedAt()?->format(DATE_ATOM),
            'rejectionReason' => $workflow->getRejectionReason(),
            'contextType' => $workflow->getContextType(),
            'contextId' => $workflow->getContextId(),
            'stepsCount' => $workflow->getSteps()->count(),
            'createdAt' => $workflow->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
