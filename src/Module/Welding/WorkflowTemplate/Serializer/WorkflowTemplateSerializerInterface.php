<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Serializer;

use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;

interface WorkflowTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowTemplateInterface $workflowTemplate): array;
}
