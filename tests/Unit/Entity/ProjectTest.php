<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Project())->getId());
    }

    public function testCollectionsInitialized(): void
    {
        $project = new Project();

        self::assertSame([], $project->getCrmContactIds());
        self::assertCount(0, $project->getTasks());
        self::assertCount(0, $project->getColumns());
    }

    public function testDefaultValues(): void
    {
        $project = new Project();

        self::assertNull($project->getReference());
        self::assertNull($project->getDescription());
        self::assertSame(ProjectStatusEnum::Draft, $project->getStatus());
        self::assertNull($project->getStartDate());
        self::assertNull($project->getEndDate());
        self::assertNull($project->getResponsibleUser());
        self::assertNull($project->getCrmCompanyId());
        self::assertNull($project->getCrmDealId());
    }

    public function testTitleGetterAndSetter(): void
    {
        $project = (new Project())->setTitle('Aurora Project');

        self::assertSame('Aurora Project', $project->getTitle());
    }

    public function testStatusGetterAndSetter(): void
    {
        $project = (new Project())->setStatus(ProjectStatusEnum::Active);

        self::assertSame(ProjectStatusEnum::Active, $project->getStatus());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $project = (new Project())->setDescription('Project description');

        self::assertSame('Project description', $project->getDescription());

        $project->setDescription(null);
        self::assertNull($project->getDescription());
    }

    public function testStartAndEndDate(): void
    {
        $start = new DateTimeImmutable('2026-01-01');
        $end = new DateTimeImmutable('2026-12-31');

        $project = (new Project())->setStartDate($start)->setEndDate($end);

        self::assertSame($start, $project->getStartDate());
        self::assertSame($end, $project->getEndDate());
    }

    public function testResponsibleUserGetterAndSetter(): void
    {
        $user = new User();
        $project = (new Project())->setResponsibleUser($user);

        self::assertSame($user, $project->getResponsibleUser());
    }

    public function testCrmCompanyAndDealIdGettersAndSetters(): void
    {
        $project = (new Project())->setCrmCompanyId(7)->setCrmDealId(9);

        self::assertSame(7, $project->getCrmCompanyId());
        self::assertSame(9, $project->getCrmDealId());
    }

    public function testCrmContactIdsGetterAndSetter(): void
    {
        $project = new Project();

        self::assertSame([], $project->getCrmContactIds());

        $project->setCrmContactIds([3, 1, 2]);
        self::assertSame([3, 1, 2], $project->getCrmContactIds());
    }

    public function testAddColumnIgnoresDuplicate(): void
    {
        $project = new Project();
        $column = new ProjectColumn();

        $project->addColumn($column);
        $project->addColumn($column);

        self::assertCount(1, $project->getColumns());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $project = (new Project())->setReference('PROJ-001');

        self::assertSame('PROJ-001', $project->getReference());
    }
}
