<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PlanningEventTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PlanningEvent())->getId());
    }

    public function testAttendeesCollectionInitialized(): void
    {
        self::assertCount(0, (new PlanningEvent())->getAttendees());
    }

    public function testDefaultValues(): void
    {
        $event = new PlanningEvent();

        self::assertNull($event->getDescription());
        self::assertNull($event->getLocation());
        self::assertFalse($event->isAllDay());
        self::assertSame(PlanningEventStatusEnum::Confirmed, $event->getStatus());
        self::assertNull($event->getSourceType());
        self::assertNull($event->getSourceId());
        self::assertNull($event->getSourceLabel());
    }

    public function testPlanningGetterAndSetter(): void
    {
        $planning = $this->createStub(PlanningInterface::class);
        $event = (new PlanningEvent())->setPlanning($planning);

        self::assertSame($planning, $event->getPlanning());
    }

    public function testTitleAndDescriptionAndLocation(): void
    {
        $event = (new PlanningEvent())
            ->setTitle('Sprint Review')
            ->setDescription('Quarterly review')
            ->setLocation('Conf Room A');

        self::assertSame('Sprint Review', $event->getTitle());
        self::assertSame('Quarterly review', $event->getDescription());
        self::assertSame('Conf Room A', $event->getLocation());
    }

    public function testStartAtAndEndAt(): void
    {
        $start = new DateTimeImmutable('2026-01-15 09:00:00');
        $end = new DateTimeImmutable('2026-01-15 10:00:00');

        $event = (new PlanningEvent())->setStartAt($start)->setEndAt($end);

        self::assertSame($start, $event->getStartAt());
        self::assertSame($end, $event->getEndAt());
    }

    public function testAllDayAndStatus(): void
    {
        $event = (new PlanningEvent())->setAllDay(true)->setStatus(PlanningEventStatusEnum::Tentative);

        self::assertTrue($event->isAllDay());
        self::assertSame(PlanningEventStatusEnum::Tentative, $event->getStatus());
    }

    public function testSourceFields(): void
    {
        $event = (new PlanningEvent())
            ->setSourceType('project_task')
            ->setSourceId(42)
            ->setSourceLabel('Build feature X');

        self::assertSame('project_task', $event->getSourceType());
        self::assertSame(42, $event->getSourceId());
        self::assertSame('Build feature X', $event->getSourceLabel());
    }

    public function testAddAndRemoveAttendee(): void
    {
        $event = new PlanningEvent();
        $user = $this->createStub(CoreUserInterface::class);

        $event->addAttendee($user);
        self::assertCount(1, $event->getAttendees());

        $event->addAttendee($user);
        self::assertCount(1, $event->getAttendees(), 'duplicate is ignored');

        $event->removeAttendee($user);
        self::assertCount(0, $event->getAttendees());
    }
}
