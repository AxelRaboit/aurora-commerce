<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\View;

use Aurora\Module\Welding\Workflow\Repository\WorkflowRepository;
use Aurora\Module\Welding\Workflow\Serializer\WorkflowSerializerInterface;
use Aurora\Module\Welding\WorkflowStep\Serializer\WorkflowStepSerializerInterface;

class WorkflowsViewBuilder
{
    public function __construct(
        protected readonly WorkflowRepository $repository,
        protected readonly WorkflowSerializerInterface $serializer,
        protected readonly WorkflowStepSerializerInterface $stepSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'workflows' => array_map(
                $this->serializer->serialize(...),
                $this->repository->findAllForIndex(),
            ),
        ];
    }
}
