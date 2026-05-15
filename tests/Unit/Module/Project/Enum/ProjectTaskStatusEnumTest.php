<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Enum;

use Aurora\Module\Project\Enum\ProjectTaskStatusEnum;
use PHPUnit\Framework\TestCase;

final class ProjectTaskStatusEnumTest extends TestCase
{
    public function testValuesReturnsAllCaseValues(): void
    {
        self::assertSame(['todo', 'in_progress', 'done', 'cancelled'], ProjectTaskStatusEnum::values());
    }

    public function testGetLabelKeyPrefixesCaseValue(): void
    {
        self::assertSame('backend.projects.task.status_todo', ProjectTaskStatusEnum::Todo->getLabelKey());
        self::assertSame('backend.projects.task.status_in_progress', ProjectTaskStatusEnum::InProgress->getLabelKey());
        self::assertSame('backend.projects.task.status_done', ProjectTaskStatusEnum::Done->getLabelKey());
        self::assertSame('backend.projects.task.status_cancelled', ProjectTaskStatusEnum::Cancelled->getLabelKey());
    }
}
