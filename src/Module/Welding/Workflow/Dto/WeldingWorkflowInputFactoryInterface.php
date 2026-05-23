<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Dto;

interface WeldingWorkflowInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingWorkflowInputInterface;
}
