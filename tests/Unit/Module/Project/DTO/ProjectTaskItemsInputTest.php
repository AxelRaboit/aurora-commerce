<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskItemsInput;
use PHPUnit\Framework\TestCase;

final class ProjectTaskItemsInputTest extends TestCase
{
    public function testFromArrayBuildsItemsFromValidEntries(): void
    {
        $input = ProjectTaskItemsInput::fromArray([
            'items' => [
                ['label' => 'First', 'done' => true],
                ['label' => 'Second', 'done' => false],
            ],
        ]);

        self::assertSame([
            ['label' => 'First', 'done' => true],
            ['label' => 'Second', 'done' => false],
        ], $input->items);
    }

    public function testFromArraySkipsItemsWithEmptyLabels(): void
    {
        $input = ProjectTaskItemsInput::fromArray([
            'items' => [
                ['label' => 'Keep', 'done' => false],
                ['label' => '', 'done' => true],     // empty label dropped
                ['label' => '   ', 'done' => false], // whitespace-only dropped
                ['label' => 'Also keep', 'done' => true],
            ],
        ]);

        self::assertCount(2, $input->items);
        self::assertSame('Keep', $input->items[0]['label']);
        self::assertSame('Also keep', $input->items[1]['label']);
    }

    public function testFromArrayCoercesDoneToBoolean(): void
    {
        $input = ProjectTaskItemsInput::fromArray([
            'items' => [
                ['label' => 'A', 'done' => 'truthy'],
                ['label' => 'B', 'done' => 0],
                ['label' => 'C'], // missing done → false
            ],
        ]);

        self::assertTrue($input->items[0]['done']);
        self::assertFalse($input->items[1]['done']);
        self::assertFalse($input->items[2]['done']);
    }

    public function testFromArrayIgnoresNonArrayItems(): void
    {
        $input = ProjectTaskItemsInput::fromArray([
            'items' => [
                ['label' => 'Valid', 'done' => false],
                'malformed',
                42,
                null,
            ],
        ]);

        self::assertCount(1, $input->items);
        self::assertSame('Valid', $input->items[0]['label']);
    }

    public function testFromArrayHandlesMissingItemsKey(): void
    {
        $input = ProjectTaskItemsInput::fromArray([]);
        self::assertSame([], $input->items);
    }
}
