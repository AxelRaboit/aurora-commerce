<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Manager;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Manager\UserManagerInterface;
use Aurora\Tests\Integration\Concern\CreatesTestUsers;
use Aurora\Tests\Integration\IntegrationTestCase;
use InvalidArgumentException;

final class UserManagerDisabledModulesTest extends IntegrationTestCase
{
    use CreatesTestUsers;

    private UserManagerInterface $userManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->userManager = static::getContainer()->get(UserManagerInterface::class);
    }

    public function testUpdateDisabledModulesPersistsKnownEntries(): void
    {
        $user = $this->createTestUser('alice', role: UserRoleEnum::User);

        $this->userManager->updateDisabledModules($user, [
            ModuleParameterEnum::CrmEnabled->value,
            ModuleParameterEnum::VaultEnabled->value,
        ]);

        self::assertEqualsCanonicalizing(
            [ModuleParameterEnum::CrmEnabled->value, ModuleParameterEnum::VaultEnabled->value],
            $user->getDisabledModules(),
        );
    }

    public function testUnknownEntriesAreFilteredSilently(): void
    {
        $user = $this->createTestUser('bob', role: UserRoleEnum::User);

        $this->userManager->updateDisabledModules($user, [
            ModuleParameterEnum::CrmEnabled->value,
            'not_a_real_module',
        ]);

        self::assertSame([ModuleParameterEnum::CrmEnabled->value], $user->getDisabledModules());
    }

    public function testDuplicatesAreDeduplicated(): void
    {
        $user = $this->createTestUser('carol', role: UserRoleEnum::User);

        $this->userManager->updateDisabledModules($user, [
            ModuleParameterEnum::CrmEnabled->value,
            ModuleParameterEnum::CrmEnabled->value,
        ]);

        self::assertSame([ModuleParameterEnum::CrmEnabled->value], $user->getDisabledModules());
    }

    public function testAdminCannotMaskModulesForDev(): void
    {
        $admin = $this->createTestUser('admin', role: UserRoleEnum::Admin);
        $dev = $this->createTestUser('dev', role: UserRoleEnum::Dev);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('backend.users.errors.cannot_manage_target');

        $this->userManager->updateDisabledModules(
            $dev,
            [ModuleParameterEnum::CrmEnabled->value],
            $admin,
        );
    }

    public function testDevCanMaskModulesForAdmin(): void
    {
        $admin = $this->createTestUser('admin', role: UserRoleEnum::Admin);
        $dev = $this->createTestUser('dev', role: UserRoleEnum::Dev);

        $this->userManager->updateDisabledModules(
            $admin,
            [ModuleParameterEnum::CrmEnabled->value],
            $dev,
        );

        self::assertSame([ModuleParameterEnum::CrmEnabled->value], $admin->getDisabledModules());
    }

    public function testAdminCanMaskModulesForUserOfEqualOrLowerRank(): void
    {
        $admin = $this->createTestUser('admin', role: UserRoleEnum::Admin);
        $regular = $this->createTestUser('regular', role: UserRoleEnum::User);

        $this->userManager->updateDisabledModules(
            $regular,
            [ModuleParameterEnum::CrmEnabled->value],
            $admin,
        );

        self::assertSame([ModuleParameterEnum::CrmEnabled->value], $regular->getDisabledModules());
    }

    public function testEmptyListClearsAllOverrides(): void
    {
        $user = $this->createTestUser('dave', role: UserRoleEnum::User);
        $this->userManager->updateDisabledModules($user, [ModuleParameterEnum::CrmEnabled->value]);

        $this->userManager->updateDisabledModules($user, []);

        self::assertSame([], $user->getDisabledModules());
    }
}
