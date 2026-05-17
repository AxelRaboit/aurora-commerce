<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Event\Serializer;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Event\Serializer\PlanningEventSerializer;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class PlanningEventSerializerTest extends TestCase
{
    private function makeEvent(?string $sourceType = null, array $attendees = []): PlanningEventInterface
    {
        $planning = $this->createStub(PlanningInterface::class);
        $planning->method('getId')->willReturn(10);

        $event = $this->createStub(PlanningEventInterface::class);
        $event->method('getId')->willReturn(1);
        $event->method('getPlanning')->willReturn($planning);
        $event->method('getTitle')->willReturn('Meeting');
        $event->method('getDescription')->willReturn('Description');
        $event->method('getLocation')->willReturn('Room A');
        $event->method('getStartAt')->willReturn(new DateTimeImmutable('2026-01-15 09:00:00'));
        $event->method('getEndAt')->willReturn(new DateTimeImmutable('2026-01-15 10:00:00'));
        $event->method('isAllDay')->willReturn(false);
        $event->method('getStatus')->willReturn(PlanningEventStatusEnum::Confirmed);
        $event->method('getSourceType')->willReturn($sourceType);
        $event->method('getSourceId')->willReturn(null);
        $event->method('getSourceLabel')->willReturn(null);
        $event->method('getAttendees')->willReturn(new ArrayCollection($attendees));

        return $event;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new PlanningEventSerializer())->serialize($this->makeEvent());

        self::assertSame(1, $result['id']);
        self::assertSame(10, $result['planningId']);
        self::assertSame('Meeting', $result['title']);
        self::assertSame('confirmed', $result['status']);
        self::assertFalse($result['allDay']);
        self::assertTrue($result['editable'], 'no source means editable');
    }

    public function testSerializeFlagsNonEditableWhenSourceSet(): void
    {
        $result = (new PlanningEventSerializer())->serialize($this->makeEvent(sourceType: 'project_task'));

        self::assertFalse($result['editable']);
        self::assertSame('project_task', $result['sourceType']);
    }

    public function testSerializeIncludesAttendees(): void
    {
        $user1 = $this->createStub(CoreUserInterface::class);
        $user1->method('getId')->willReturn(1);
        $user1->method('getName')->willReturn('Alice');

        $user2 = $this->createStub(CoreUserInterface::class);
        $user2->method('getId')->willReturn(2);
        $user2->method('getName')->willReturn('Bob');

        $result = (new PlanningEventSerializer())->serialize($this->makeEvent(attendees: [$user1, $user2]));

        self::assertCount(2, $result['attendees']);
        self::assertSame(['id' => 1, 'name' => 'Alice'], $result['attendees'][0]);
        self::assertSame(['id' => 2, 'name' => 'Bob'], $result['attendees'][1]);
    }
}
