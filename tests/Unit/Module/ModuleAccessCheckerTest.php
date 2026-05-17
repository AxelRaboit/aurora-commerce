<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Core\Module\Toggle\ModuleToggleRegistry;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class ModuleAccessCheckerTest extends TestCase
{
    public function testGloballyDisabledReturnsFalseRegardlessOfUser(): void
    {
        $checker = $this->makeChecker(global: [ModuleParameterEnum::VaultBackend->value => false]);
        $user = $this->makeUser([]);

        self::assertFalse($checker->isEnabled(ModuleParameterEnum::VaultBackend, $user));
    }

    public function testGloballyEnabledAndNoUserOverrideReturnsTrue(): void
    {
        $checker = $this->makeChecker(global: []); // missing = default true
        $user = $this->makeUser([]);

        self::assertTrue($checker->isEnabled(ModuleParameterEnum::VaultBackend, $user));
    }

    public function testUserOverrideMasksGloballyEnabledModule(): void
    {
        $checker = $this->makeChecker(global: []);
        $user = $this->makeUser([ModuleParameterEnum::CrmBackend->value]);

        self::assertFalse($checker->isEnabled(ModuleParameterEnum::CrmBackend, $user));
        self::assertTrue($checker->isEnabled(ModuleParameterEnum::VaultBackend, $user));
    }

    public function testUserOverrideOnParentCascadesToChildren(): void
    {
        $checker = $this->makeChecker(global: []);
        $user = $this->makeUser([ModuleParameterEnum::CrmBackend->value]);

        // CrmContactsEnabled cascadeRequires CrmEnabled
        self::assertFalse($checker->isEnabled(ModuleParameterEnum::CrmContacts, $user));
        // CrmDealsEnabled cascadeRequires CrmContactsEnabled → CrmEnabled (transitive)
        self::assertFalse($checker->isEnabled(ModuleParameterEnum::CrmDeals, $user));
    }

    public function testGlobalParentDisabledCascadesToChildren(): void
    {
        $checker = $this->makeChecker(global: [
            ModuleParameterEnum::EditorialBackend->value => false,
        ]);
        $user = $this->makeUser([]);

        self::assertFalse($checker->isEnabled(ModuleParameterEnum::EditorialPosts, $user));
        self::assertFalse($checker->isEnabled(ModuleParameterEnum::EditorialSitemap, $user));
    }

    public function testNoAuthenticatedUserOnlyAppliesGlobalSetting(): void
    {
        $checker = $this->makeChecker(global: [], currentUser: null);

        self::assertTrue($checker->isEnabled(ModuleParameterEnum::VaultBackend));
    }

    public function testCurrentUserIsUsedWhenUserArgIsNull(): void
    {
        $user = $this->makeUser([ModuleParameterEnum::VaultBackend->value]);
        $checker = $this->makeChecker(global: [], currentUser: $user);

        self::assertFalse($checker->isEnabled(ModuleParameterEnum::VaultBackend));
    }

    public function testIsGloballyEnabledIgnoresUserOverride(): void
    {
        $user = $this->makeUser([ModuleParameterEnum::VaultBackend->value]);
        $checker = $this->makeChecker(global: [], currentUser: $user);

        self::assertTrue($checker->isGloballyEnabled(ModuleParameterEnum::VaultBackend));
    }

    public function testIsMaskedForUserReturnsTrueOnlyWhenExplicitlyListed(): void
    {
        $checker = $this->makeChecker(global: []);
        $user = $this->makeUser([ModuleParameterEnum::CrmBackend->value]);

        self::assertTrue($checker->isMaskedForUser(ModuleParameterEnum::CrmBackend, $user));
        self::assertFalse($checker->isMaskedForUser(ModuleParameterEnum::CrmContacts, $user));
    }

    /**
     * @param array<string, bool> $global mapping enum value => bool (missing keys default to true)
     */
    private function makeChecker(array $global, ?CoreUserInterface $currentUser = null): ModuleAccessChecker
    {
        $repo = $this->createMock(SettingRepository::class);
        $repo->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default = false): bool => $global[$key] ?? $default,
        );

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($currentUser);

        // Registry seeded from every ModuleParameterEnum case so the cascade
        // graph mirrors what aurora-core's modules declare in production.
        $registry = new ModuleToggleRegistry([new InMemoryToggleProvider()]);

        return new ModuleAccessChecker($repo, $security, $registry);
    }

    /** @param list<string> $disabled */
    private function makeUser(array $disabled): CoreUserInterface
    {
        $user = $this->createMock(CoreUserInterface::class);
        $user->method('getDisabledModules')->willReturn($disabled);

        return $user;
    }
}

/**
 * Test fixture that exposes every `ModuleParameterEnum` case as a toggle.
 * Used to feed `ModuleToggleRegistry` with the full core cascade graph
 * without instantiating the real `*Module` classes (which would require
 * mocking `final readonly` context services).
 */
final class InMemoryToggleProvider implements ModuleInterface, ModuleToggleProviderInterface
{
    public function getId(): string
    {
        return 'in_memory';
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getNavSections(): array
    {
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }

    public function getToggles(): array
    {
        return array_map(
            static fn (ModuleParameterEnum $case): ModuleToggle => $case->toToggle(),
            ModuleParameterEnum::cases(),
        );
    }
}
