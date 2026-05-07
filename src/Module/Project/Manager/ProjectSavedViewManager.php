<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectSavedViewManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /** @param array<string, mixed> $filters */
    public function create(User $owner, Project $project, string $name, array $filters): ProjectSavedView
    {
        $view = new ProjectSavedView();
        $view->setOwner($owner)->setProject($project)->setName($name)->setFilters($filters);
        $this->entityManager->persist($view);
        $this->entityManager->flush();

        return $view;
    }

    /** @param array<string, mixed> $filters */
    public function update(ProjectSavedView $view, string $name, array $filters): void
    {
        $view->setName($name)->setFilters($filters);
        $this->entityManager->flush();
    }

    public function delete(ProjectSavedView $view): void
    {
        $this->entityManager->remove($view);
        $this->entityManager->flush();
    }
}
