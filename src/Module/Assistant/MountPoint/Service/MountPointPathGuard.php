<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Service;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;

/**
 * Central path-allowlist for every filesystem tool the assistant ships.
 *
 * Before this lived inlined in {@see FilesystemReadTool},
 * {@see FilesystemWriteTool}, {@see FilesystemSearchTool} and
 * {@see ImageReadTool} — four near-identical realpath + prefix-match
 * implementations. The security boundary is single-source-of-truth
 * here so a future tweak (e.g. switching ReadOnly enforcement on read
 * too) lands in exactly one place.
 *
 * `realpath()` is intentionally called on every input: it collapses
 * `..`, resolves symlinks, and returns false for non-existing paths,
 * which is what we want — a symlink that points outside the allowlist
 * resolves to its real target and fails the prefix check.
 */
final readonly class MountPointPathGuard
{
    public function __construct(
        private AssistantMountPointRepository $mountPointRepository,
    ) {}

    /**
     * @param bool $requireWrite If true, only ReadWrite mount points count
     *                           towards the allowlist
     */
    public function isAllowed(string $resolvedPath, CoreUserInterface $user, bool $requireWrite = false): bool
    {
        foreach ($this->activeBases($user, $requireWrite) as $base) {
            if ($resolvedPath === $base) {
                return true;
            }

            if (str_starts_with($resolvedPath, $base.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Real (canonical) absolute paths of every active mount point that
     * the user can use for `$requireWrite` (defaults to read-and-write,
     * i.e. all active mount points).
     *
     * @return list<string>
     */
    public function activeBases(CoreUserInterface $user, bool $requireWrite = false): array
    {
        $bases = [];
        foreach ($this->mountPointRepository->findActiveForUser($user) as $mountPoint) {
            if ($requireWrite && MountPointAccessEnum::ReadWrite !== $mountPoint->getAccess()) {
                continue;
            }

            $base = realpath($mountPoint->getPath());
            if (false === $base) {
                continue;
            }

            $bases[] = $base;
        }

        return $bases;
    }
}
