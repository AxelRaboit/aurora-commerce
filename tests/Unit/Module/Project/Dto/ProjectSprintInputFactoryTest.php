<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectSprintInputFactory;
use PHPUnit\Framework\TestCase;

final class ProjectSprintInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new ProjectSprintInputFactory())->fromArray([
            'name' => 'Sprint 1',
            'startDate' => '2026-01-01',
            'endDate' => '2026-01-14',
            'isActive' => true,
        ]);

        self::assertSame('Sprint 1', $input->getName());
        self::assertSame('2026-01-01', $input->getStartDate());
        self::assertSame('2026-01-14', $input->getEndDate());
        self::assertTrue($input->isActive());
    }

    public function testFromArrayWithDefaults(): void
    {
        $input = (new ProjectSprintInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getStartDate());
        self::assertNull($input->getEndDate());
        self::assertFalse($input->isActive());
    }
}
