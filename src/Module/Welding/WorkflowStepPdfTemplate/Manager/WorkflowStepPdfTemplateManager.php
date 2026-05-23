<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WorkflowStepPdfTemplateInputInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WorkflowStepTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStepPdfTemplateManagerInterface::class)]
class WorkflowStepPdfTemplateManager implements WorkflowStepPdfTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WorkflowStepTemplateRepository $stepRepository,
        protected readonly PdfTemplateRepository $pdfTemplateRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WorkflowStepPdfTemplateInputInterface $input): WorkflowStepPdfTemplateInterface
    {
        $entry = $this->createWorkflowStepPdfTemplate();
        $this->applyInput($entry, $input);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditCreated($entry);

        return $entry;
    }

    public function update(WorkflowStepPdfTemplateInterface $entry, WorkflowStepPdfTemplateInputInterface $input): void
    {
        $this->applyInput($entry, $input);
        $this->entityManager->flush();

        $this->auditUpdated($entry);
    }

    public function delete(WorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditDeleted($entry);

        $this->entityManager->remove($entry);
        $this->entityManager->flush();
    }

    protected function createWorkflowStepPdfTemplate(): WorkflowStepPdfTemplateInterface
    {
        return new WorkflowStepPdfTemplate();
    }

    protected function applyInput(WorkflowStepPdfTemplateInterface $entry, WorkflowStepPdfTemplateInputInterface $input): void
    {
        if (null === $entry->getWorkflowStepTemplate() && null !== $input->getWorkflowStepTemplateId()) {
            $step = $this->stepRepository->find($input->getWorkflowStepTemplateId());
            if (null === $step) {
                throw new RuntimeException(sprintf('WorkflowStepTemplate #%d not found', $input->getWorkflowStepTemplateId()));
            }
            $entry->setWorkflowStepTemplate($step);
        }

        if (null === $entry->getPdfTemplate() && null !== $input->getPdfTemplateId()) {
            $pdfTemplate = $this->pdfTemplateRepository->find($input->getPdfTemplateId());
            if (null === $pdfTemplate) {
                throw new RuntimeException(sprintf('PdfTemplate #%d not found', $input->getPdfTemplateId()));
            }
            $entry->setPdfTemplate($pdfTemplate);
        }

        $entry->setPosition($input->getPosition());
        $entry->setRequired($input->isRequired());
    }

    protected function auditCreated(WorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.created', 'WorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditUpdated(WorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.updated', 'WorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditDeleted(WorkflowStepPdfTemplateInterface $entry): void
    {
        $this->auditLogger->log('welding', 'workflow_step_pdf_template.deleted', 'WorkflowStepPdfTemplate', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditPayload(WorkflowStepPdfTemplateInterface $entry): array
    {
        return [
            'stepId' => $entry->getWorkflowStepTemplate()?->getId(),
            'pdfTemplateId' => $entry->getPdfTemplate()?->getId(),
            'position' => $entry->getPosition(),
            'required' => $entry->getRequired(),
        ];
    }
}
