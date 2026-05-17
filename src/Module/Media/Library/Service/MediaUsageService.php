<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Service;

use Aurora\Module\Media\Library\Contract\MediaUsageProviderInterface;

/**
 * Aggregates usages of a Media across all modules. Returned shape is grouped
 * by source type so the UI can render readable sections.
 *
 * Module providers are pulled in via the `aurora.media_usage_provider` tag;
 * adding a new module that references Media just means implementing the
 * interface — no changes here.
 */
final readonly class MediaUsageService
{
    /** @param iterable<MediaUsageProviderInterface> $providers */
    public function __construct(private iterable $providers) {}

    /**
     * @return array{total: int, groups: list<array{type: string, items: list<array<string, mixed>>}>}
     */
    public function findUsages(int $mediaId): array
    {
        $byType = [];
        $total = 0;
        foreach ($this->providers as $provider) {
            foreach ($provider->findUsages($mediaId) as $usage) {
                $type = $usage['type'];
                $byType[$type] ??= [];
                $byType[$type][] = $usage;
                ++$total;
            }
        }

        $groups = [];
        foreach ($byType as $type => $items) {
            $groups[] = ['type' => $type, 'items' => $items];
        }

        return ['total' => $total, 'groups' => $groups];
    }
}
