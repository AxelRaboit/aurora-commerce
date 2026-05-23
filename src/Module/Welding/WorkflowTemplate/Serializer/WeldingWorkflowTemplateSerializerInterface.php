<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Serializer;

use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;

interface WeldingWorkflowTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowTemplateInterface $workflowTemplate): array;
}
