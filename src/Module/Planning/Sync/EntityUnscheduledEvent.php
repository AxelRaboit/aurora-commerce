<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Sync;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when the upstream entity is deleted/cancelled and its
 * planning entry should be removed.
 */
final class EntityUnscheduledEvent extends Event
{
    public function __construct(
        public readonly string $sourceType,
        public readonly int $sourceId,
    ) {}
}
