<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Entity\ProjectSavedViewInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectSavedViewManagerInterface::class)]
class ProjectSavedViewManager implements ProjectSavedViewManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {}

    /** @param array<string, mixed> $filters */
    public function create(User $owner, ProjectInterface $project, string $name, array $filters): ProjectSavedViewInterface
    {
        $view = $this->createProjectSavedView();
        $view->setOwner($owner)->setProject($project)->setName($name)->setFilters($filters);
        $this->entityManager->persist($view);
        $this->entityManager->flush();

        return $view;
    }

    /** @param array<string, mixed> $filters */
    public function update(ProjectSavedViewInterface $view, string $name, array $filters): void
    {
        $view->setName($name)->setFilters($filters);
        $this->entityManager->flush();
    }

    public function delete(ProjectSavedViewInterface $view): void
    {
        $this->entityManager->remove($view);
        $this->entityManager->flush();
    }

    protected function createProjectSavedView(): ProjectSavedViewInterface
    {
        return new ProjectSavedView();
    }
}
