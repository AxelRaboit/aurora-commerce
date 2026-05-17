<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;
use Aurora\Module\Project\Serializer\ProjectTaskTimeEntrySerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ProjectTaskTimeEntrySerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 7);
        $user->setName('Jane Doe');

        $entry = $this->createStub(ProjectTaskTimeEntryInterface::class);
        $entry->method('getId')->willReturn(1);
        $entry->method('getMinutes')->willReturn(45);
        $entry->method('getNote')->willReturn('Pair programming');
        $entry->method('getLoggedAt')->willReturn(new DateTimeImmutable('2026-01-15'));
        $entry->method('getUser')->willReturn($user);

        $result = (new ProjectTaskTimeEntrySerializer())->serialize($entry);

        self::assertSame(1, $result['id']);
        self::assertSame(45, $result['minutes']);
        self::assertSame('Pair programming', $result['note']);
        self::assertSame('2026-01-15', $result['loggedAt']);
        self::assertSame(7, $result['userId']);
        self::assertSame('Jane Doe', $result['userName']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $user = new User();
        $user->setName('User');

        $entry = $this->createStub(ProjectTaskTimeEntryInterface::class);
        $entry->method('getId')->willReturn(1);
        $entry->method('getMinutes')->willReturn(0);
        $entry->method('getNote')->willReturn(null);
        $entry->method('getLoggedAt')->willReturn(new DateTimeImmutable());
        $entry->method('getUser')->willReturn($user);

        $result = (new ProjectTaskTimeEntrySerializer())->serialize($entry);

        self::assertSame(['id', 'minutes', 'note', 'loggedAt', 'userId', 'userName'], array_keys($result));
    }
}
