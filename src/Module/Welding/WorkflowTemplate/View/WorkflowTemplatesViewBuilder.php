<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\View;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer\WorkflowStepPdfTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Serializer\WorkflowStepTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Serializer\WorkflowTemplateSerializerInterface;

class WorkflowTemplatesViewBuilder
{
    public function __construct(
        protected readonly WorkflowTemplateRepository $repository,
        protected readonly WorkflowTemplateSerializerInterface $serializer,
        protected readonly WorkflowStepTemplateSerializerInterface $stepSerializer,
        protected readonly WorkflowStepPdfTemplateSerializerInterface $stepPdfSerializer,
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

    /** @return array<string, mixed> */
    public function editorView(WorkflowTemplateInterface $template): array
    {
        $steps = array_map(
            fn (WorkflowStepTemplateInterface $step): array => [
                ...$this->stepSerializer->serialize($step),
                'pdfTemplates' => array_map(
                    $this->stepPdfSerializer->serialize(...),
                    $step->getPdfTemplates()->toArray(),
                ),
            ],
            $template->getSteps()->toArray(),
        );

        return [
            'workflowTemplate' => $this->serializer->serialize($template),
            'steps' => $steps,
        ];
    }
}
