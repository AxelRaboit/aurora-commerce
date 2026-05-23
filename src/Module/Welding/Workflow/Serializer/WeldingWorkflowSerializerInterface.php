<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Serializer;

use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;

interface WeldingWorkflowSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowInterface $workflow): array;
}
