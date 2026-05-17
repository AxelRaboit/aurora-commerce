<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectTaskTimeEntryTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectTaskTimeEntry())->getId());
    }

    public function testDefaultValues(): void
    {
        $entry = new ProjectTaskTimeEntry();

        self::assertSame(0, $entry->getMinutes());
        self::assertNull($entry->getNote());
    }

    public function testMinutesGetterAndSetter(): void
    {
        $entry = (new ProjectTaskTimeEntry())->setMinutes(45);

        self::assertSame(45, $entry->getMinutes());
    }

    public function testNoteGetterAndSetter(): void
    {
        $entry = (new ProjectTaskTimeEntry())->setNote('Pair programming session');

        self::assertSame('Pair programming session', $entry->getNote());

        $entry->setNote(null);
        self::assertNull($entry->getNote());
    }

    public function testTaskGetterAndSetter(): void
    {
        $task = new ProjectTask();
        $entry = (new ProjectTaskTimeEntry())->setTask($task);

        self::assertSame($task, $entry->getTask());
    }

    public function testUserGetterAndSetter(): void
    {
        $user = new User();
        $entry = (new ProjectTaskTimeEntry())->setUser($user);

        self::assertSame($user, $entry->getUser());
    }

    public function testLoggedAtGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-01-15 10:00:00');
        $entry = (new ProjectTaskTimeEntry())->setLoggedAt($date);

        self::assertSame($date, $entry->getLoggedAt());
    }
}
