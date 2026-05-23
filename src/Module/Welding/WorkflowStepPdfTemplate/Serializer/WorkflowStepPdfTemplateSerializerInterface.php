<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;

interface WorkflowStepPdfTemplateSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(WorkflowStepPdfTemplateInterface $entry): array;
}
