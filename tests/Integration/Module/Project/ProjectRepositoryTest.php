<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\Project;

use Aurora\Module\Project\Dto\ProjectInput;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Tests\Integration\IntegrationTestCase;

final class ProjectRepositoryTest extends IntegrationTestCase
{
    private ProjectRepository $repository;
    private ProjectManager $manager;
    /** Per-test unique prefix so search filters target only data we just created. */
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->repository = static::getContainer()->get(ProjectRepository::class);
        $this->manager = static::getContainer()->get(ProjectManager::class);
        $this->prefix = 'PRT_'.uniqid().'_';
    }

    public function testFindPaginatedReturnsExpectedShape(): void
    {
        $this->manager->create(new ProjectInput(title: $this->prefix.'Alpha', status: ProjectStatusEnum::Active->value));

        $result = $this->repository->findPaginated(1, search: $this->prefix);

        self::assertArrayHasKey('items', $result);
        self::assertArrayHasKey('total', $result);
        self::assertArrayHasKey('page', $result);
        self::assertArrayHasKey('totalPages', $result);
        self::assertSame(1, $result['total']);
    }

    public function testFindPaginatedFiltersByStatus(): void
    {
        $this->manager->create(new ProjectInput(title: $this->prefix.'Active', status: ProjectStatusEnum::Active->value));
        $this->manager->create(new ProjectInput(title: $this->prefix.'Cancelled', status: ProjectStatusEnum::Cancelled->value));

        $result = $this->repository->findPaginated(1, search: $this->prefix, status: ProjectStatusEnum::Cancelled);

        self::assertSame(1, $result['total']);
        self::assertSame($this->prefix.'Cancelled', $result['items'][0]->getTitle());
    }

    public function testFindPaginatedFiltersBySearchCaseInsensitive(): void
    {
        $this->manager->create(new ProjectInput(title: $this->prefix.'Refonte du Site', status: ProjectStatusEnum::Active->value));

        $result = $this->repository->findPaginated(1, search: mb_strtolower($this->prefix).'refonte');

        self::assertGreaterThanOrEqual(1, $result['total']);
    }

    public function testFindPaginatedSearchAndStatusCombined(): void
    {
        $this->manager->create(new ProjectInput(title: $this->prefix.'Audit', status: ProjectStatusEnum::Completed->value));
        $this->manager->create(new ProjectInput(title: $this->prefix.'Audit', status: ProjectStatusEnum::Active->value));

        $result = $this->repository->findPaginated(1, search: $this->prefix, status: ProjectStatusEnum::Completed);

        self::assertSame(1, $result['total']);
    }
}
