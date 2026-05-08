<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskInput;
use Aurora\Module\Project\Dto\ProjectTaskInputFactory;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use PHPUnit\Framework\TestCase;

final class ProjectTaskInputTest extends TestCase
{
    public function testFromArrayDefaultsPriorityToMediumWhenMissing(): void
    {
        $input = (new ProjectTaskInputFactory())->fromArray(['title' => 'X']);

        self::assertSame(ProjectTaskPriorityEnum::Medium->value, $input->priority);
        self::assertSame(ProjectTaskPriorityEnum::Medium, $input->getPriorityEnum());
    }

    public function testNormalizesLabelIdsAndWatcherIds(): void
    {
        $input = (new ProjectTaskInputFactory())->fromArray([
            'title' => 'X',
            'labelIds' => [1, '2', '', 0, -1, 3, 3],
            'watcherIds' => ['10', null, 20, 20],
        ]);

        self::assertSame([1, 2, 3], $input->labelIds);
        self::assertSame([10, 20], $input->watcherIds);
    }

    public function testStoryPointsAndEstimateAreCoercedFromStrings(): void
    {
        $input = (new ProjectTaskInputFactory())->fromArray([
            'title' => 'X',
            'storyPoints' => '5',
            'estimateMinutes' => '120',
        ]);

        self::assertSame(5, $input->storyPoints);
        self::assertSame(120, $input->estimateMinutes);
    }

    public function testEmptyStringsBecomeNullForOptionalIntegerFields(): void
    {
        $input = (new ProjectTaskInputFactory())->fromArray([
            'title' => 'X',
            'storyPoints' => '',
            'estimateMinutes' => '',
            'sprintId' => '',
            'columnId' => '',
        ]);

        self::assertNull($input->storyPoints);
        self::assertNull($input->estimateMinutes);
        self::assertNull($input->sprintId);
        self::assertNull($input->columnId);
    }

    public function testFromArrayPositionDefaultsToZero(): void
    {
        $input = (new ProjectTaskInputFactory())->fromArray(['title' => 'X']);
        self::assertSame(0, $input->position);
    }
}
