<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingWorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Dto\WeldingWorkflowTemplateInputInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingWorkflowTemplateManagerInterface::class)]
class WeldingWorkflowTemplateManager implements WeldingWorkflowTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WeldingWorkflowTemplateRepository $repository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WeldingWorkflowTemplateInputInterface $input): WeldingWorkflowTemplateInterface
    {
        $workflowTemplate = $this->createWorkflowTemplate();
        $this->applyInput($workflowTemplate, $input);
        $workflowTemplate->setVersion(1);
        $workflowTemplate->setStatus(WeldingWorkflowTemplateStatusEnum::Draft);

        $this->entityManager->persist($workflowTemplate);
        $this->entityManager->flush();

        $this->auditCreated($workflowTemplate);

        return $workflowTemplate;
    }

    public function update(WeldingWorkflowTemplateInterface $workflowTemplate, WeldingWorkflowTemplateInputInterface $input): void
    {
        $this->applyInput($workflowTemplate, $input);
        $this->entityManager->flush();

        $this->auditUpdated($workflowTemplate);
    }

    public function delete(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditDeleted($workflowTemplate);

        $this->entityManager->remove($workflowTemplate);
        $this->entityManager->flush();
    }

    public function publish(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $workflowTemplate->setStatus(WeldingWorkflowTemplateStatusEnum::Published);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.published', 'WeldingWorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    public function archive(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $workflowTemplate->setStatus(WeldingWorkflowTemplateStatusEnum::Archived);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.archived', 'WeldingWorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    public function cloneAsNewVersion(WeldingWorkflowTemplateInterface $source): WeldingWorkflowTemplateInterface
    {
        $clone = $this->createWorkflowTemplate();
        $clone->setTitle($source->getTitle());
        $clone->setDescription($source->getDescription());
        $clone->setApplicableTo($source->getApplicableTo());
        $clone->setVersion($source->getVersion() + 1);
        $clone->setStatus(WeldingWorkflowTemplateStatusEnum::Draft);
        $clone->setParentVersion($source);

        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.cloned', 'WeldingWorkflowTemplate', $clone->getId(), [
            ...$this->auditPayload($clone),
            'parentId' => $source->getId(),
            'parentVersion' => $source->getVersion(),
        ]);

        return $clone;
    }

    protected function createWorkflowTemplate(): WeldingWorkflowTemplateInterface
    {
        return new WeldingWorkflowTemplate();
    }

    protected function applyInput(WeldingWorkflowTemplateInterface $workflowTemplate, WeldingWorkflowTemplateInputInterface $input): void
    {
        $workflowTemplate->setTitle($input->getTitle());
        $workflowTemplate->setDescription($input->getDescription());
        $workflowTemplate->setApplicableTo($input->getApplicableTo());
    }

    protected function auditCreated(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.created', 'WeldingWorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditUpdated(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.updated', 'WeldingWorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditDeleted(WeldingWorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.deleted', 'WeldingWorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditPayload(WeldingWorkflowTemplateInterface $workflowTemplate): array
    {
        return [
            'title' => $workflowTemplate->getTitle(),
            'version' => $workflowTemplate->getVersion(),
            'status' => $workflowTemplate->getStatus()->value,
        ];
    }
}
