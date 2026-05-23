<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\View;

use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;
use Aurora\Module\Welding\PdfDocument\Repository\WeldingPdfDocumentRepository;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\Workflow\Serializer\WeldingWorkflowSerializerInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStep\Serializer\WeldingWorkflowStepSerializerInterface;
use Aurora\Module\Welding\WorkflowStepTask\Serializer\WeldingWorkflowStepTaskSerializerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;

use const DATE_ATOM;

class WeldingWorkflowRunnerViewBuilder
{
    public const string PDF_CONTEXT_TYPE = 'welding_step';

    public function __construct(
        protected readonly WeldingWorkflowSerializerInterface $workflowSerializer,
        protected readonly WeldingWorkflowStepSerializerInterface $stepSerializer,
        protected readonly WeldingPdfDocumentRepository $pdfDocumentRepository,
        protected readonly WeldingWorkflowStepTaskSerializerInterface $stepTaskSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function runnerView(WeldingWorkflowInterface $workflow): array
    {
        return [
            'workflow' => $this->workflowSerializer->serialize($workflow),
            'steps' => array_map(
                $this->serializeStepWithPdfs(...),
                $workflow->getSteps()->toArray(),
            ),
            'pdfContextType' => self::PDF_CONTEXT_TYPE,
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeStepWithPdfs(WeldingWorkflowStepInterface $step): array
    {
        $base = $this->stepSerializer->serialize($step);
        $base['pdfTemplates'] = $this->collectPdfTemplates($step);
        $base['tasks'] = array_map(
            $this->stepTaskSerializer->serialize(...),
            $step->getTasks()->toArray(),
        );

        return $base;
    }

    /** @return array<int, array<string, mixed>> */
    protected function collectPdfTemplates(WeldingWorkflowStepInterface $step): array
    {
        $stepTemplate = $step->getStepTemplate();
        if (!$stepTemplate instanceof WeldingWorkflowStepTemplateInterface) {
            return [];
        }

        $stepId = $step->getId();
        $generated = null === $stepId ? [] : $this->pdfDocumentRepository->findByContext(self::PDF_CONTEXT_TYPE, $stepId);

        // Index generated docs by WeldingPdfTemplate id for fast lookup
        $generatedByTemplate = [];
        foreach ($generated as $doc) {
            $tid = $doc->getTemplate()?->getId();
            if (null !== $tid) {
                $generatedByTemplate[$tid][] = $doc;
            }
        }

        $rows = [];
        foreach ($stepTemplate->getPdfTemplates() as $entry) {
            $pdfTemplate = $entry->getPdfTemplate();
            if (null === $pdfTemplate) {
                continue;
            }

            $rows[] = [
                'id' => $entry->getId(),
                'pdfTemplateId' => $pdfTemplate->getId(),
                'pdfTemplateName' => $pdfTemplate->getName(),
                'required' => $entry->getRequired(),
                'position' => $entry->getPosition(),
                'generatedDocuments' => array_map(
                    fn (WeldingPdfDocumentInterface $doc): array => [
                        'id' => $doc->getId(),
                        'reference' => $doc->getReference(),
                        'label' => $doc->getLabel(),
                        'filePath' => $doc->getFilePath(),
                        'createdAt' => $doc->getCreatedAt()->format(DATE_ATOM),
                    ],
                    $generatedByTemplate[$pdfTemplate->getId()] ?? [],
                ),
            ];
        }

        return $rows;
    }
}
