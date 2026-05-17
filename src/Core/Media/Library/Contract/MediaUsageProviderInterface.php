<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Contract;

/**
 * Implemented by modules that reference Media entities (Editorial posts,
 * Photo galleries, Setting branding…). The aggregator service iterates all
 * tagged providers to surface a complete usage list before deletion.
 *
 * Tag implementing classes with `aurora.media_usage_provider` (autoconfigured
 * via the interface alias).
 */
interface MediaUsageProviderInterface
{
    /**
     * @return list<array{type: string, label: string, detail?: ?string, href?: ?string}>
     *
     *  - type   : machine identifier of the source (e.g. "post.featured", "gallery.cover")
     *  - label  : human-readable target name (post title, gallery title, "Logo du site"…)
     *  - detail : optional secondary description ("Image principale", "Bloc image", "Cover"…)
     *  - href   : optional admin URL to navigate to the source
     */
    public function findUsages(int $mediaId): array;
}
