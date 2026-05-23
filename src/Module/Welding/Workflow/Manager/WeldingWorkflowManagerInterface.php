<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Manager;

use Aurora\Module\Welding\Workflow\Dto\WeldingWorkflowInputInterface;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;

interface WeldingWorkflowManagerInterface
{
    public function create(WeldingWorkflowInputInterface $input): WeldingWorkflowInterface;

    public function start(WeldingWorkflowInterface $workflow): void;

    public function reject(WeldingWorkflowInterface $workflow, string $reason): void;

    public function archive(WeldingWorkflowInterface $workflow): void;

    public function delete(WeldingWorkflowInterface $workflow): void;
}
