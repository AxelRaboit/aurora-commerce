<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface;

interface WeldingWorkflowStepPdfTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WeldingWorkflowStepPdfTemplateInterface $entry): array;
}
