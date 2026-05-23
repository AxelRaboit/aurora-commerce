<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;

interface WeldingWorkflowStepTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepTemplateInterface $step): array;
}
