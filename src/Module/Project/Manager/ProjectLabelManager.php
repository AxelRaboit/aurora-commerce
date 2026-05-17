<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectLabelInputInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectLabelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectLabelManagerInterface::class)]
class ProjectLabelManager implements ProjectLabelManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(ProjectInterface $project, ProjectLabelInputInterface $input): ProjectLabelInterface
    {
        $label = $this->createProjectLabel();
        $label->setProject($project)->setName($input->getName())->setColor($input->getColor());
        $this->entityManager->persist($label);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.created', 'ProjectLabel', $label->getId(), [
            ...$this->auditPayload($label),
            'projectId' => $project->getId(),
        ]);

        return $label;
    }

    public function update(ProjectLabelInterface $label, ProjectLabelInputInterface $input): void
    {
        $label->setName($input->getName())->setColor($input->getColor());
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.updated', 'ProjectLabel', $label->getId(), [
            ...$this->auditPayload($label),
            'projectId' => $label->getProject()->getId(),
        ]);
    }

    public function delete(ProjectLabelInterface $label): void
    {
        $projectId = $label->getProject()->getId();
        $payload = $this->auditPayload($label);
        $id = $label->getId();
        $this->entityManager->remove($label);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.deleted', 'ProjectLabel', $id, [
            ...$payload,
            'projectId' => $projectId,
        ]);
    }

    protected function createProjectLabel(): ProjectLabelInterface
    {
        return new ProjectLabel();
    }

    protected function auditPayload(ProjectLabelInterface $label): array
    {
        return ['name' => $label->getName(), 'color' => $label->getColor()];
    }
}
