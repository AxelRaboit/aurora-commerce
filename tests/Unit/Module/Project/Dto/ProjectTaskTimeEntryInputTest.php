<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInput;
use PHPUnit\Framework\TestCase;

final class ProjectTaskTimeEntryInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new ProjectTaskTimeEntryInput();

        self::assertSame(0, $input->getMinutes());
        self::assertNull($input->getNote());
        self::assertNull($input->getLoggedAt());
    }

    public function testConstructorValues(): void
    {
        $input = new ProjectTaskTimeEntryInput(45, 'Pair programming', '2026-01-15');

        self::assertSame(45, $input->getMinutes());
        self::assertSame('Pair programming', $input->getNote());
        self::assertSame('2026-01-15', $input->getLoggedAt());
    }
}
