<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Project\DTO\ProjectSprintInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSprint;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectSprintManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function create(Project $project, ProjectSprintInput $input): ProjectSprint
    {
        $sprint = new ProjectSprint();
        $sprint->setProject($project);
        $this->applyInput($sprint, $input);
        $this->entityManager->persist($sprint);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.created', 'ProjectSprint', $sprint->getId(), [
            'projectId' => $project->getId(),
            'name' => $sprint->getName(),
        ]);

        return $sprint;
    }

    public function update(ProjectSprint $sprint, ProjectSprintInput $input): void
    {
        $this->applyInput($sprint, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.updated', 'ProjectSprint', $sprint->getId(), [
            'projectId' => $sprint->getProject()->getId(),
            'name' => $sprint->getName(),
        ]);
    }

    public function delete(ProjectSprint $sprint): void
    {
        $sprintId = $sprint->getId();
        $projectId = $sprint->getProject()->getId();
        $name = $sprint->getName();

        $this->entityManager->remove($sprint);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.deleted', 'ProjectSprint', $sprintId, [
            'projectId' => $projectId,
            'name' => $name,
        ]);
    }

    private function applyInput(ProjectSprint $sprint, ProjectSprintInput $input): void
    {
        $sprint->setName($input->name)
            ->setStartDate($input->startDate ? new DateTimeImmutable($input->startDate) : null)
            ->setEndDate($input->endDate ? new DateTimeImmutable($input->endDate) : null)
            ->setIsActive($input->isActive);
    }
}
