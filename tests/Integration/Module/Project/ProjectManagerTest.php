<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\Project;

use Aurora\Module\Project\DTO\ProjectInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectManagerTest extends IntegrationTestCase
{
    private ProjectManager $manager;
    private ProjectRepository $projectRepository;
    private ProjectColumnRepository $columnRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::getContainer()->get(ProjectManager::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->columnRepository = static::getContainer()->get(ProjectColumnRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateAssignsReferenceAndSeedsColumns(): void
    {
        $input = new ProjectInput(
            title: 'New Project',
            status: ProjectStatusEnum::Draft->value,
        );

        $project = $this->manager->create($input);

        self::assertNotNull($project->getId());
        self::assertNotNull($project->getReference(), 'reference should be assigned by SequenceGenerator');
        self::assertStringStartsWith('PRJ-', (string) $project->getReference());

        // 3 default columns must exist after create.
        $columns = $this->columnRepository->findByProject($project);
        self::assertCount(3, $columns);
        self::assertSame(0, $columns[0]->getPosition());
        self::assertSame(2, $columns[2]->getPosition());
    }

    public function testCreatePersistsScalarsCorrectly(): void
    {
        $input = new ProjectInput(
            title: 'Refonte',
            description: 'A description',
            status: ProjectStatusEnum::Active->value,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
        );

        $project = $this->manager->create($input);
        $this->entityManager->clear();
        $reloaded = $this->projectRepository->find($project->getId());

        self::assertInstanceOf(Project::class, $reloaded);
        self::assertSame('Refonte', $reloaded->getTitle());
        self::assertSame('A description', $reloaded->getDescription());
        self::assertSame(ProjectStatusEnum::Active, $reloaded->getStatus());
        self::assertSame('2026-01-01', $reloaded->getStartDate()?->format('Y-m-d'));
    }

    public function testUpdateAppliesChanges(): void
    {
        $project = $this->manager->create(new ProjectInput(title: 'Old', status: ProjectStatusEnum::Draft->value));

        $this->manager->update($project, new ProjectInput(
            title: 'New title',
            description: 'Updated',
            status: ProjectStatusEnum::Completed->value,
        ));

        $this->entityManager->clear();
        $reloaded = $this->projectRepository->find($project->getId());

        self::assertSame('New title', $reloaded->getTitle());
        self::assertSame('Updated', $reloaded->getDescription());
        self::assertSame(ProjectStatusEnum::Completed, $reloaded->getStatus());
    }

    public function testDeleteCascadesToColumns(): void
    {
        $project = $this->manager->create(new ProjectInput(title: 'To delete', status: ProjectStatusEnum::Draft->value));
        $projectId = $project->getId();

        $this->manager->delete($project);
        $this->entityManager->clear();

        self::assertNull($this->projectRepository->find($projectId));
        // Columns should be gone via cascade.
        $remaining = $this->entityManager->getRepository(ProjectColumn::class)
            ->findBy(['project' => $projectId]);
        self::assertSame([], $remaining);
    }
}
