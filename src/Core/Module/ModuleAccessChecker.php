<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\CoreUserInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Single source of truth for module enablement.
 *
 * Combines three layers, in order:
 *  1. Global setting (toggle stored in `core_settings`) — managed by dev panel.
 *  2. Per-user override (CoreUserInterface::getDisabledModules()) — managed by admin/dev.
 *  3. Cascade graph — if any ancestor toggle (via `parentKey`) is OFF at any
 *     layer for this user, the toggle is OFF too.
 *
 * Toggles are resolved through {@see ModuleToggleRegistry}, which aggregates
 * declarations from every module implementing {@see ModuleToggleProviderInterface}.
 * This includes aurora-core's `ModuleParameterEnum` cases and any toggle
 * declared by an aurora-client module — they are treated uniformly.
 *
 * The public API accepts either a `ModuleParameterEnum` (for core call sites
 * that benefit from type safety) or a raw string key (for client modules
 * with toggles not present in the enum).
 */
class ModuleAccessChecker
{
    /** @var array<string, bool> */
    private array $globalCache = [];

    public function __construct(
        protected readonly SettingRepository $settingRepository,
        protected readonly Security $security,
        protected readonly ModuleToggleRegistry $toggleRegistry,
    ) {}

    /**
     * Returns whether the given toggle is accessible.
     * When $user is null, the currently authenticated user is used.
     * When no user is authenticated, only the global setting + cascade apply.
     */
    public function isEnabled(ModuleParameterEnum|string $toggle, ?CoreUserInterface $user = null): bool
    {
        $key = $toggle instanceof ModuleParameterEnum ? $toggle->value : $toggle;
        $user ??= $this->resolveCurrentUser();

        return $this->checkKey($key, $user);
    }

    /**
     * Returns true when the global setting is on, regardless of user overrides.
     */
    public function isGloballyEnabled(ModuleParameterEnum|string $toggle): bool
    {
        $key = $toggle instanceof ModuleParameterEnum ? $toggle->value : $toggle;

        return $this->getGlobal($key);
    }

    /**
     * Returns true when the given user has explicitly masked this toggle
     * (independent of the global setting and cascade).
     */
    public function isMaskedForUser(ModuleParameterEnum|string $toggle, CoreUserInterface $user): bool
    {
        $key = $toggle instanceof ModuleParameterEnum ? $toggle->value : $toggle;

        return in_array($key, $user->getDisabledModules(), true);
    }

    private function checkKey(string $key, ?CoreUserInterface $user): bool
    {
        if (!$this->getGlobal($key)) {
            return false;
        }

        if ($user instanceof CoreUserInterface && in_array($key, $user->getDisabledModules(), true)) {
            return false;
        }

        $parentKey = $this->resolveParentKey($key);
        if (null !== $parentKey && !$this->checkKey($parentKey, $user)) {
            return false;
        }

        return true;
    }

    private function resolveParentKey(string $key): ?string
    {
        return $this->toggleRegistry->get($key)?->parentKey;
    }

    private function getGlobal(string $key): bool
    {
        return $this->globalCache[$key] ??= $this->settingRepository->getBoolean($key, true);
    }

    private function resolveCurrentUser(): ?CoreUserInterface
    {
        $current = $this->security->getUser();

        return $current instanceof CoreUserInterface ? $current : null;
    }
}
