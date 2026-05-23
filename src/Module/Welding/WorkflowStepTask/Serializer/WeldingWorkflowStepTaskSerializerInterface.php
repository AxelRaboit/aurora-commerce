<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Serializer;

use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;

interface WeldingWorkflowStepTaskSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepTaskInterface $task): array;
}
