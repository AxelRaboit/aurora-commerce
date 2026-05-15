<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Enum;

use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use PHPUnit\Framework\TestCase;

final class ProjectTaskPriorityEnumTest extends TestCase
{
    public function testValuesReturnsAllCases(): void
    {
        self::assertSame(['low', 'medium', 'high', 'urgent'], ProjectTaskPriorityEnum::values());
    }

    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.projects.task.priority_low', ProjectTaskPriorityEnum::Low->getLabelKey());
        self::assertSame('backend.projects.task.priority_urgent', ProjectTaskPriorityEnum::Urgent->getLabelKey());
    }
}
