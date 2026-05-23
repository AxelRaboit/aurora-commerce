<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Serializer;

use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(WeldingWorkflowStepTaskSerializerInterface::class)]
class WeldingWorkflowStepTaskSerializer implements WeldingWorkflowStepTaskSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepTaskInterface $task): array
    {
        $doneBy = $task->getDoneBy();

        return [
            'id' => $task->getId(),
            'workflowStepId' => $task->getWorkflowStep()?->getId(),
            'label' => $task->getLabel(),
            'description' => $task->getDescription(),
            'position' => $task->getPosition(),
            'required' => $task->getRequired(),
            'done' => $task->getDone(),
            'doneById' => $doneBy?->getId(),
            'doneByName' => $doneBy?->getName(),
            'doneAt' => $task->getDoneAt()?->format(DATE_ATOM),
        ];
    }
}
