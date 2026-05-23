<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowInputFactoryInterface::class)]
class WorkflowInputFactory implements WorkflowInputFactoryInterface
{
    public function fromArray(array $data): WorkflowInputInterface
    {
        $templateId = $data['templateId'] ?? null;
        $assigneeId = $data['assigneeId'] ?? null;
        $contextId = $data['contextId'] ?? null;

        return new WorkflowInput(
            templateId: null === $templateId ? null : (int) $templateId,
            assigneeId: null === $assigneeId ? null : (int) $assigneeId,
            contextType: Str::trimOrNullFromArray($data, 'contextType'),
            contextId: null === $contextId ? null : (int) $contextId,
        );
    }
}
