<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Project\DTO\ProjectLabelInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectLabelManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function create(Project $project, ProjectLabelInput $input): ProjectLabel
    {
        $label = new ProjectLabel();
        $label->setProject($project)->setName($input->name)->setColor($input->color);
        $this->entityManager->persist($label);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.created', 'ProjectLabel', $label->getId(), [
            'projectId' => $project->getId(),
            'name' => $input->name,
            'color' => $input->color,
        ]);

        return $label;
    }

    public function update(ProjectLabel $label, ProjectLabelInput $input): void
    {
        $label->setName($input->name)->setColor($input->color);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.updated', 'ProjectLabel', $label->getId(), [
            'projectId' => $label->getProject()->getId(),
            'name' => $input->name,
            'color' => $input->color,
        ]);
    }

    public function delete(ProjectLabel $label): void
    {
        $projectId = $label->getProject()->getId();
        $name = $label->getName();
        $id = $label->getId();
        $this->entityManager->remove($label);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'label.deleted', 'ProjectLabel', $id, [
            'projectId' => $projectId,
            'name' => $name,
        ]);
    }
}
