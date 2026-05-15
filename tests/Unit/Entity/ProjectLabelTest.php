<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use PHPUnit\Framework\TestCase;

final class ProjectLabelTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectLabel())->getId());
    }

    public function testColorDefaultsToAccent(): void
    {
        self::assertSame('accent', (new ProjectLabel())->getColor());
    }

    public function testNameGetterAndSetter(): void
    {
        $label = (new ProjectLabel())->setName('Bug');

        self::assertSame('Bug', $label->getName());
    }

    public function testColorGetterAndSetter(): void
    {
        $label = (new ProjectLabel())->setColor('red');

        self::assertSame('red', $label->getColor());
    }

    public function testProjectGetterAndSetter(): void
    {
        $project = new Project();
        $label = (new ProjectLabel())->setProject($project);

        self::assertSame($project, $label->getProject());
    }
}
