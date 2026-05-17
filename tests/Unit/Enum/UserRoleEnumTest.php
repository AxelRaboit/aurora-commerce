<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Enum;

use Aurora\Core\Platform\User\Enum\UserRoleEnum;
use PHPUnit\Framework\TestCase;

final class UserRoleEnumTest extends TestCase
{
    public function testPriorityOrder(): void
    {
        self::assertGreaterThan(UserRoleEnum::Admin->priority(), UserRoleEnum::Dev->priority());
        self::assertGreaterThan(UserRoleEnum::User->priority(), UserRoleEnum::Admin->priority());
    }

    public function testSelectableForAdmin(): void
    {
        $selectable = UserRoleEnum::selectableForAdmin();

        self::assertNotContains(UserRoleEnum::Dev, $selectable);
        self::assertContains(UserRoleEnum::Admin, $selectable);
        self::assertContains(UserRoleEnum::User, $selectable);
    }

    public function testAllAssignableValues(): void
    {
        $assignable = UserRoleEnum::allAssignableValues();

        self::assertContains(UserRoleEnum::Dev->value, $assignable);
    }

    public function testLabelKey(): void
    {
        foreach (UserRoleEnum::cases() as $case) {
            self::assertNotEmpty($case->getLabelKey(), sprintf('Label key for %s must not be empty', $case->name));
        }
    }

    public function testHighestPriorityForRolesReturnsMaxPriority(): void
    {
        $roles = [UserRoleEnum::User->value, UserRoleEnum::Admin->value];

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
