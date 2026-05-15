<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectSprintInterface;
use Aurora\Module\Project\Serializer\ProjectSprintSerializer;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ProjectSprintSerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $sprint = $this->createStub(ProjectSprintInterface::class);
        $sprint->method('getId')->willReturn(1);
        $sprint->method('getName')->willReturn('Sprint 1');
        $sprint->method('getStartDate')->willReturn(new DateTimeImmutable('2026-01-01'));
        $sprint->method('getEndDate')->willReturn(new DateTimeImmutable('2026-01-14'));
        $sprint->method('isActive')->willReturn(true);
        $sprint->method('getTasks')->willReturn(new ArrayCollection([1, 2, 3]));

        $result = (new ProjectSprintSerializer())->serialize($sprint);

        self::assertSame(1, $result['id']);
        self::assertSame('Sprint 1', $result['name']);
        self::assertSame('2026-01-01', $result['startDate']);
        self::assertSame('2026-01-14', $result['endDate']);
        self::assertTrue($result['isActive']);
        self::assertSame(3, $result['taskCount']);
    }

    public function testSerializeWithNullDates(): void
    {
        $sprint = $this->createStub(ProjectSprintInterface::class);
        $sprint->method('getId')->willReturn(1);
        $sprint->method('getName')->willReturn('Sprint');
        $sprint->method('getStartDate')->willReturn(null);
        $sprint->method('getEndDate')->willReturn(null);
        $sprint->method('isActive')->willReturn(false);
        $sprint->method('getTasks')->willReturn(new ArrayCollection());

        $result = (new ProjectSprintSerializer())->serialize($sprint);

        self::assertNull($result['startDate']);
        self::assertNull($result['endDate']);
        self::assertSame(0, $result['taskCount']);
    }
}
