<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\View;

use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Serializer\WorkflowTemplateSerializerInterface;

class WorkflowTemplatesViewBuilder
{
    public function __construct(
        protected readonly WorkflowTemplateRepository $repository,
        protected readonly WorkflowTemplateSerializerInterface $serializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'workflowTemplates' => array_map(
                $this->serializer->serialize(...),
                $this->repository->findAllForIndex(),
            ),
        ];
    }
}
