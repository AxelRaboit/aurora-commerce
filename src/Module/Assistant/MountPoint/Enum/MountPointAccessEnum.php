<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Enum;

/**
 * Whether the assistant is allowed to mutate the contents of a mount point.
 * Read tools always run against any active mount point of the owning user;
 * write tools (Phase 1B+) refuse to run against ReadOnly entries.
 */
enum MountPointAccessEnum: string
{
    case ReadOnly = 'read_only';
    case ReadWrite = 'read_write';
}
