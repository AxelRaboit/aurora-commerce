<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSprint;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectSprintTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectSprint())->getId());
    }

    public function testTasksCollectionInitialized(): void
    {
        self::assertCount(0, (new ProjectSprint())->getTasks());
    }

    public function testDefaultValues(): void
    {
        $sprint = new ProjectSprint();

        self::assertNull($sprint->getStartDate());
        self::assertNull($sprint->getEndDate());
        self::assertFalse($sprint->isActive());
    }

    public function testNameGetterAndSetter(): void
    {
        $sprint = (new ProjectSprint())->setName('Sprint 1');

        self::assertSame('Sprint 1', $sprint->getName());
    }

    public function testProjectGetterAndSetter(): void
    {
        $project = new Project();
        $sprint = (new ProjectSprint())->setProject($project);

        self::assertSame($project, $sprint->getProject());
    }

    public function testStartDateGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-01-01');
        $sprint = (new ProjectSprint())->setStartDate($date);

        self::assertSame($date, $sprint->getStartDate());

        $sprint->setStartDate(null);
        self::assertNull($sprint->getStartDate());
    }

    public function testEndDateGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-01-14');
        $sprint = (new ProjectSprint())->setEndDate($date);

        self::assertSame($date, $sprint->getEndDate());

        $sprint->setEndDate(null);
        self::assertNull($sprint->getEndDate());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $sprint = (new ProjectSprint())->setIsActive(true);

        self::assertTrue($sprint->isActive());
    }
}
