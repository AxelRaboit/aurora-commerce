<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Dto\WorkflowTemplateInputInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowTemplateManagerInterface::class)]
class WorkflowTemplateManager implements WorkflowTemplateManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly WorkflowTemplateRepository $repository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(WorkflowTemplateInputInterface $input): WorkflowTemplateInterface
    {
        $workflowTemplate = $this->createWorkflowTemplate();
        $this->applyInput($workflowTemplate, $input);
        $workflowTemplate->setVersion(1);
        $workflowTemplate->setStatus(WorkflowTemplateStatusEnum::Draft);

        $this->entityManager->persist($workflowTemplate);
        $this->entityManager->flush();

        $this->auditCreated($workflowTemplate);

        return $workflowTemplate;
    }

    public function update(WorkflowTemplateInterface $workflowTemplate, WorkflowTemplateInputInterface $input): void
    {
        $this->applyInput($workflowTemplate, $input);
        $this->entityManager->flush();

        $this->auditUpdated($workflowTemplate);
    }

    public function delete(WorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditDeleted($workflowTemplate);

        $this->entityManager->remove($workflowTemplate);
        $this->entityManager->flush();
    }

    public function publish(WorkflowTemplateInterface $workflowTemplate): void
    {
        $workflowTemplate->setStatus(WorkflowTemplateStatusEnum::Published);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.published', 'WorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    public function archive(WorkflowTemplateInterface $workflowTemplate): void
    {
        $workflowTemplate->setStatus(WorkflowTemplateStatusEnum::Archived);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.archived', 'WorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    public function cloneAsNewVersion(WorkflowTemplateInterface $source): WorkflowTemplateInterface
    {
        $clone = $this->createWorkflowTemplate();
        $clone->setTitle($source->getTitle());
        $clone->setDescription($source->getDescription());
        $clone->setApplicableTo($source->getApplicableTo());
        $clone->setVersion($source->getVersion() + 1);
        $clone->setStatus(WorkflowTemplateStatusEnum::Draft);
        $clone->setParentVersion($source);

        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        $this->auditLogger->log('welding', 'workflow_template.cloned', 'WorkflowTemplate', $clone->getId(), [
            ...$this->auditPayload($clone),
            'parentId' => $source->getId(),
            'parentVersion' => $source->getVersion(),
        ]);

        return $clone;
    }

    protected function createWorkflowTemplate(): WorkflowTemplateInterface
    {
        return new WorkflowTemplate();
    }

    protected function applyInput(WorkflowTemplateInterface $workflowTemplate, WorkflowTemplateInputInterface $input): void
    {
        $workflowTemplate->setTitle($input->getTitle());
        $workflowTemplate->setDescription($input->getDescription());
        $workflowTemplate->setApplicableTo($input->getApplicableTo());
    }

    protected function auditCreated(WorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.created', 'WorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditUpdated(WorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.updated', 'WorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditDeleted(WorkflowTemplateInterface $workflowTemplate): void
    {
        $this->auditLogger->log('welding', 'workflow_template.deleted', 'WorkflowTemplate', $workflowTemplate->getId(), $this->auditPayload($workflowTemplate));
    }

    protected function auditPayload(WorkflowTemplateInterface $workflowTemplate): array
    {
        return [
            'title' => $workflowTemplate->getTitle(),
            'version' => $workflowTemplate->getVersion(),
            'status' => $workflowTemplate->getStatus()->value,
        ];
    }
}
