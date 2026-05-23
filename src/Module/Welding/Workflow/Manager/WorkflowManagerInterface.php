<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Manager;

use Aurora\Module\Welding\Workflow\Dto\WorkflowInputInterface;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;

interface WorkflowManagerInterface
{
    public function create(WorkflowInputInterface $input): WorkflowInterface;

    public function start(WorkflowInterface $workflow): void;

    public function reject(WorkflowInterface $workflow, string $reason): void;

    public function archive(WorkflowInterface $workflow): void;

    public function delete(WorkflowInterface $workflow): void;
}
