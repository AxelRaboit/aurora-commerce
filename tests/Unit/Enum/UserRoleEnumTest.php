<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Core\User\Enum\UserRoleEnum;
use PHPUnit\Framework\TestCase;

final class UserRoleEnumTest extends TestCase
{
    public function testPriorityOrder(): void
    {
        self::assertGreaterThan(UserRoleEnum::Admin->priority(), UserRoleEnum::Dev->priority());
        self::assertGreaterThan(UserRoleEnum::Editor->priority(), UserRoleEnum::Admin->priority());
        self::assertGreaterThan(UserRoleEnum::Author->priority(), UserRoleEnum::Editor->priority());
        self::assertGreaterThan(UserRoleEnum::Contributor->priority(), UserRoleEnum::Author->priority());
        self::assertGreaterThan(UserRoleEnum::User->priority(), UserRoleEnum::Contributor->priority());
    }

    public function testSelectableForAdmin(): void
    {
        $selectable = UserRoleEnum::selectableForAdmin();

        self::assertNotContains(UserRoleEnum::Dev, $selectable);
        self::assertContains(UserRoleEnum::Admin, $selectable);
    }

    public function testAllAssignableValues(): void
    {
        $assignable = UserRoleEnum::allAssignableValues();

        self::assertContains(UserRoleEnum::Dev->value, $assignable);
    }

    public function testLabel(): void
    {
        foreach (UserRoleEnum::cases() as $case) {
            self::assertNotEmpty($case->label(), sprintf('Label for %s must not be empty', $case->name));
        }
    }

    public function testHighestPriorityForRolesReturnsMaxPriority(): void
    {
        $roles = [UserRoleEnum::User->value, UserRoleEnum::Admin->value, UserRoleEnum::Editor->value];

        self::assertSame(UserRoleEnum::Admin->priority(), UserRoleEnum::highestPriorityForRoles($roles));
    }

    public function testHighestPriorityForRolesWithEmptyArray(): void
    {
        self::assertSame(0, UserRoleEnum::highestPriorityForRoles([]));
    }

    public function testHighestPriorityForRolesWithUnknownRoles(): void
    {
        self::assertSame(0, UserRoleEnum::highestPriorityForRoles(['ROLE_UNKNOWN']));
    }

    public function testHighestPriorityForRolesWithDevRole(): void
    {
        $roles = [UserRoleEnum::User->value, UserRoleEnum::Dev->value];

        self::assertSame(UserRoleEnum::Dev->priority(), UserRoleEnum::highestPriorityForRoles($roles));
    }
}
