<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Serializer;

use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;

interface WeldingWorkflowStepSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepInterface $step): array;
}
