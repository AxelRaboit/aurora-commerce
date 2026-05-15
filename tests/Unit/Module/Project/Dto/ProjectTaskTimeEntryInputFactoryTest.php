<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInputFactory;
use PHPUnit\Framework\TestCase;

final class ProjectTaskTimeEntryInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new ProjectTaskTimeEntryInputFactory())->fromArray([
            'minutes' => '45',
            'note' => '  Note  ',
            'loggedAt' => '  2026-01-15  ',
        ]);

        self::assertSame(45, $input->getMinutes());
        self::assertSame('Note', $input->getNote());
        self::assertSame('2026-01-15', $input->getLoggedAt());
    }

    public function testFromArrayWithDefaults(): void
    {
        $input = (new ProjectTaskTimeEntryInputFactory())->fromArray([]);

        self::assertSame(0, $input->getMinutes());
        self::assertNull($input->getNote());
        self::assertNull($input->getLoggedAt());
    }

    public function testFromArrayEmptyMinutesIsZero(): void
    {
        $input = (new ProjectTaskTimeEntryInputFactory())->fromArray(['minutes' => '']);

        self::assertSame(0, $input->getMinutes());
    }
}
