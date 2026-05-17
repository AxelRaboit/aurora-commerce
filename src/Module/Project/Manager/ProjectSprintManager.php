<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectSprintInputInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectSprintInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectSprintManagerInterface::class)]
class ProjectSprintManager implements ProjectSprintManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(ProjectInterface $project, ProjectSprintInputInterface $input): ProjectSprintInterface
    {
        $sprint = $this->createProjectSprint();
        $sprint->setProject($project);
        $this->applyInput($sprint, $input);
        $this->entityManager->persist($sprint);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.created', 'ProjectSprint', $sprint->getId(), [
            ...$this->auditPayload($sprint),
            'projectId' => $project->getId(),
        ]);

        return $sprint;
    }

    public function update(ProjectSprintInterface $sprint, ProjectSprintInputInterface $input): void
    {
        $this->applyInput($sprint, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.updated', 'ProjectSprint', $sprint->getId(), [
            ...$this->auditPayload($sprint),
            'projectId' => $sprint->getProject()->getId(),
        ]);
    }

    public function delete(ProjectSprintInterface $sprint): void
    {
        $sprintId = $sprint->getId();
        $projectId = $sprint->getProject()->getId();
        $payload = $this->auditPayload($sprint);

        $this->entityManager->remove($sprint);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'sprint.deleted', 'ProjectSprint', $sprintId, [
            ...$payload,
            'projectId' => $projectId,
        ]);
    }

    protected function createProjectSprint(): ProjectSprintInterface
    {
        return new ProjectSprint();
    }

    protected function applyInput(ProjectSprintInterface $sprint, ProjectSprintInputInterface $input): void
    {
        $sprint->setName($input->getName())
            ->setStartDate($input->getStartDate() ? new DateTimeImmutable($input->getStartDate()) : null)
            ->setEndDate($input->getEndDate() ? new DateTimeImmutable($input->getEndDate()) : null)
            ->setIsActive($input->isActive());
    }

    protected function auditPayload(ProjectSprintInterface $sprint): array
    {
        return ['name' => $sprint->getName()];
    }
}
