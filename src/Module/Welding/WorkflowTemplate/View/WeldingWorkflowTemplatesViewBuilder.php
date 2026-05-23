<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\View;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer\WeldingWorkflowStepPdfTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Serializer\WeldingWorkflowStepTaskTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Serializer\WeldingWorkflowStepTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Serializer\WeldingWorkflowTemplateSerializerInterface;

class WeldingWorkflowTemplatesViewBuilder
{
    public function __construct(
        protected readonly WeldingWorkflowTemplateRepository $repository,
        protected readonly WeldingWorkflowTemplateSerializerInterface $serializer,
        protected readonly WeldingWorkflowStepTemplateSerializerInterface $stepSerializer,
        protected readonly WeldingWorkflowStepPdfTemplateSerializerInterface $stepPdfSerializer,
        protected readonly WeldingWorkflowStepTaskTemplateSerializerInterface $stepTaskSerializer,
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
    public function editorView(WeldingWorkflowTemplateInterface $template): array
    {
        $steps = array_map(
            fn (WeldingWorkflowStepTemplateInterface $step): array => [
                ...$this->stepSerializer->serialize($step),
                'pdfTemplates' => array_map(
                    $this->stepPdfSerializer->serialize(...),
                    $step->getPdfTemplates()->toArray(),
                ),
                'tasks' => array_map(
                    $this->stepTaskSerializer->serialize(...),
                    $step->getTasks()->toArray(),
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
