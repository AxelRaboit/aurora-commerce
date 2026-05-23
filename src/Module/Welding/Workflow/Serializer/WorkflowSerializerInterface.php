<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Serializer;

use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;

interface WorkflowSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowInterface $workflow): array;
}
