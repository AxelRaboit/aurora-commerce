<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Serializer;

use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;

interface WorkflowStepSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowStepInterface $step): array;
}
