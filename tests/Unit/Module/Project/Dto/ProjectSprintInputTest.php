<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectSprintInput;
use PHPUnit\Framework\TestCase;

final class ProjectSprintInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new ProjectSprintInput();

        self::assertSame('', $input->getName());
        self::assertNull($input->getStartDate());
        self::assertNull($input->getEndDate());
        self::assertFalse($input->isActive());
    }

    public function testConstructorValues(): void
    {
        $input = new ProjectSprintInput('Sprint 1', '2026-01-01', '2026-01-14', true);

        self::assertSame('Sprint 1', $input->getName());
        self::assertSame('2026-01-01', $input->getStartDate());
        self::assertSame('2026-01-14', $input->getEndDate());
        self::assertTrue($input->isActive());
    }
}
