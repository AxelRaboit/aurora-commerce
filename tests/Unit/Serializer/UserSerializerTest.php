<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Serializer\UserSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class UserSerializerTest extends TestCase
{
    private UserSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new UserSerializer();
    }

    public function testRolePriorityReflectsDevRoleEvenThoughBadgeHidesIt(): void
    {
        $user = $this->makeUser([UserRoleEnum::Dev->value]);

        $payload = $this->serializer->serialize($user);

        // The role badge intentionally omits Dev → role/roleLabel are null.
        self::assertNull($payload['role']);
        self::assertNull($payload['roleLabel']);
        self::assertTrue($payload['isDev']);

        // …but the effective priority MUST still reflect Dev so canActOn works correctly.
        self::assertSame(UserRoleEnum::Dev->priority(), $payload['rolePriority']);
    }

    public function testRolePriorityForAdmin(): void
    {
        $user = $this->makeUser([UserRoleEnum::Admin->value]);

        $payload = $this->serializer->serialize($user);

        self::assertSame(UserRoleEnum::Admin->value, $payload['role']);
        self::assertSame(UserRoleEnum::Admin->priority(), $payload['rolePriority']);
        self::assertFalse($payload['isDev']);
    }

    public function testRolePriorityPicksHighestWhenMultipleRolesPresent(): void
    {
        $user = $this->makeUser([UserRoleEnum::Editor->value, UserRoleEnum::Dev->value]);

        $payload = $this->serializer->serialize($user);

        self::assertSame(UserRoleEnum::Dev->priority(), $payload['rolePriority']);
        self::assertTrue($payload['isDev']);
    }

    /** @param list<string> $roles */
    private function makeUser(array $roles): User
    {
        $user = new User();
        $user->setName('Test');
        $user->setEmail('test@aurora.test');
        $user->setRoles($roles);

        // TimestampableTrait fields are normally populated by lifecycle callbacks; bypass that here.
        $createdAt = new ReflectionProperty($user, 'createdAt');
        $createdAt->setValue($user, new DateTimeImmutable('2026-01-01 00:00:00'));

        return $user;
    }
}
