<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\View;

use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Aurora\Module\PdfForm\PdfDocument\Repository\PdfDocumentRepository;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\Workflow\Serializer\WorkflowSerializerInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStep\Serializer\WorkflowStepSerializerInterface;

use const DATE_ATOM;

class WorkflowRunnerViewBuilder
{
    public const string PDF_CONTEXT_TYPE = 'welding_step';

    public function __construct(
        protected readonly WorkflowSerializerInterface $workflowSerializer,
        protected readonly WorkflowStepSerializerInterface $stepSerializer,
        protected readonly PdfDocumentRepository $pdfDocumentRepository,
    ) {}

    /** @return array<string, mixed> */
    public function runnerView(WorkflowInterface $workflow): array
    {
        return [
            'workflow' => $this->workflowSerializer->serialize($workflow),
            'steps' => array_map(
                fn (WorkflowStepInterface $step): array => $this->serializeStepWithPdfs($step),
                $workflow->getSteps()->toArray(),
            ),
            'pdfContextType' => self::PDF_CONTEXT_TYPE,
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeStepWithPdfs(WorkflowStepInterface $step): array
    {
        $base = $this->stepSerializer->serialize($step);
        $base['pdfTemplates'] = $this->collectPdfTemplates($step);

        return $base;
    }

    /** @return array<int, array<string, mixed>> */
    protected function collectPdfTemplates(WorkflowStepInterface $step): array
    {
        $stepTemplate = $step->getStepTemplate();
        if (null === $stepTemplate) {
            return [];
        }

        $stepId = $step->getId();
        $generated = null === $stepId ? [] : $this->pdfDocumentRepository->findByContext(self::PDF_CONTEXT_TYPE, $stepId);

        // Index generated docs by PdfTemplate id for fast lookup
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
                    fn (PdfDocumentInterface $doc): array => [
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
