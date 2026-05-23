<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowInputFactoryInterface::class)]
class WeldingWorkflowInputFactory implements WeldingWorkflowInputFactoryInterface
{
    public function fromArray(array $data): WeldingWorkflowInputInterface
    {
        $templateId = $data['templateId'] ?? null;
        $assigneeId = $data['assigneeId'] ?? null;
        $contextId = $data['contextId'] ?? null;

        return new WeldingWorkflowInput(
            templateId: null === $templateId ? null : (int) $templateId,
            assigneeId: null === $assigneeId ? null : (int) $assigneeId,
            contextType: Str::trimOrNullFromArray($data, 'contextType'),
            contextId: null === $contextId ? null : (int) $contextId,
        );
    }
}
