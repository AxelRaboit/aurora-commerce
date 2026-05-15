<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use PHPUnit\Framework\TestCase;

final class ProjectTaskItemTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectTaskItem())->getId());
    }

    public function testDefaultValues(): void
    {
        $item = new ProjectTaskItem();

        self::assertFalse($item->isDone());
        self::assertSame(0, $item->getPosition());
    }

    public function testLabelGetterAndSetter(): void
    {
        $item = (new ProjectTaskItem())->setLabel('Write tests');

        self::assertSame('Write tests', $item->getLabel());
    }

    public function testDoneGetterAndSetter(): void
    {
        $item = (new ProjectTaskItem())->setDone(true);

        self::assertTrue($item->isDone());
    }

    public function testPositionGetterAndSetter(): void
    {
        $item = (new ProjectTaskItem())->setPosition(3);

        self::assertSame(3, $item->getPosition());
    }

    public function testTaskGetterAndSetter(): void
    {
        $task = new ProjectTask();
        $item = (new ProjectTaskItem())->setTask($task);

        self::assertSame($task, $item->getTask());
    }
}
