<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use PHPUnit\Framework\TestCase;

final class ProjectColumnTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectColumn())->getId());
    }

    public function testTasksCollectionInitialized(): void
    {
        self::assertCount(0, (new ProjectColumn())->getTasks());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $column = new ProjectColumn();
        self::assertNull($column->getReference());

        $column->setReference('REF-COL-001');
        self::assertSame('REF-COL-001', $column->getReference());

        $column->setReference(null);
        self::assertNull($column->getReference());
    }

    public function testLabelGetterAndSetter(): void
    {
        $column = (new ProjectColumn())->setLabel('In Progress');

        self::assertSame('In Progress', $column->getLabel());
    }

    public function testPositionDefaultsToZero(): void
    {
        self::assertSame(0, (new ProjectColumn())->getPosition());
    }

    public function testPositionGetterAndSetter(): void
    {
        $column = (new ProjectColumn())->setPosition(5);

        self::assertSame(5, $column->getPosition());
    }

    public function testProjectGetterAndSetter(): void
    {
        $project = new Project();
        $column = (new ProjectColumn())->setProject($project);

        self::assertSame($project, $column->getProject());
    }

    public function testSettersReturnSelf(): void
    {
        $column = new ProjectColumn();

        self::assertSame($column, $column->setReference('r'));
        self::assertSame($column, $column->setProject(new Project()));
        self::assertSame($column, $column->setLabel('l'));
        self::assertSame($column, $column->setPosition(1));
    }
}
