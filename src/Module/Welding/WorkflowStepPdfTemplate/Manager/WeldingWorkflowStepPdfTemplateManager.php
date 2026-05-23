<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WeldingWorkflowStepPdfTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowStepPdfTemplateManagerInterface::class)]
class WeldingWorkflowStepPdfTemplateManager implements WeldingWorkflowStepPdfTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingWorkflowStepTemplateRepository $stepRepository,
        protected readonly WeldingPdfTemplateRepository $pdfTemplateRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WeldingWorkflowStepPdfTemplateInputInterface $input): WeldingWorkflowStepPdfTemplateInterface
    {
        $entry = $this->createWorkflowStepPdfTemplate();
        $this->applyInput($entry, $input);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditCreated($entry);

        return $entry;
    }

    public function update(WeldingWorkflowStepPdfTemplateInterface $entry, WeldingWorkflowStepPdfTemplateInputInterface $input): void
    {
        $this->applyInput($entry, $input);
        $this->entityManager->flush();

        $this->auditUpdated($entry);
    }

    public function delete(WeldingWorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditDeleted($entry);

        $this->entityManager->remove($entry);
        $this->entityManager->flush();
    }

    protected function createWorkflowStepPdfTemplate(): WeldingWorkflowStepPdfTemplateInterface
    {
        return new WeldingWorkflowStepPdfTemplate();
    }

    protected function applyInput(WeldingWorkflowStepPdfTemplateInterface $entry, WeldingWorkflowStepPdfTemplateInputInterface $input): void
    {
        if (!$entry->getWorkflowStepTemplate() instanceof WeldingWorkflowStepTemplateInterface && null !== $input->getWorkflowStepTemplateId()) {
            $step = $this->stepRepository->find($input->getWorkflowStepTemplateId());
            if (null === $step) {
                throw new RuntimeException(sprintf('WeldingWorkflowStepTemplate #%d not found', $input->getWorkflowStepTemplateId()));
            }

            $entry->setWorkflowStepTemplate($step);
        }

        if (!$entry->getPdfTemplate() instanceof WeldingPdfTemplateInterface && null !== $input->getPdfTemplateId()) {
            $pdfTemplate = $this->pdfTemplateRepository->find($input->getPdfTemplateId());
            if (null === $pdfTemplate) {
                throw new RuntimeException(sprintf('WeldingPdfTemplate #%d not found', $input->getPdfTemplateId()));
            }

            $entry->setPdfTemplate($pdfTemplate);
        }

        $entry->setPosition($input->getPosition());
        $entry->setRequired($input->isRequired());
    }

    protected function auditCreated(WeldingWorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.created', 'WeldingWorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditUpdated(WeldingWorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.updated', 'WeldingWorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditDeleted(WeldingWorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.deleted', 'WeldingWorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditPayload(WeldingWorkflowStepPdfTemplateInterface $entry): array
    {
        return [
            'stepId' => $entry->getWorkflowStepTemplate()?->getId(),
            'pdfTemplateId' => $entry->getPdfTemplate()?->getId(),
            'position' => $entry->getPosition(),
            'required' => $entry->getRequired(),
        ];
    }
}
