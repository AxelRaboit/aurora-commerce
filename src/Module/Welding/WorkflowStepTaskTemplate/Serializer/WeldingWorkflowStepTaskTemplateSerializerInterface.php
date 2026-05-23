<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;

interface WeldingWorkflowStepTaskTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepTaskTemplateInterface $entry): array;
}
