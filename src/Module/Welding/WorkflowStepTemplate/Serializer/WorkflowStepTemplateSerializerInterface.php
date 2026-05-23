<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;

interface WorkflowStepTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowStepTemplateInterface $step): array;
}
