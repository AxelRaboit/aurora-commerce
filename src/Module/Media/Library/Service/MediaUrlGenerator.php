<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Service;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Single point that turns a {@see MediaInterface} (or one of its
 * variants) into the user-facing URL pointing at
 * {@see UploadsServeController}.
 *
 * Lives here rather than on `AbstractMedia` so the entity stays a
 * pure domain object — URL building requires `UrlGeneratorInterface`,
 * a presentation concern entities should not depend on. See
 * CLAUDE.md §5bis and CLAUDE.md §3bis
 * ("penser long terme").
 *
 * Consumers: every serializer / Twig extension / page renderer that
 * used to call `$media->getPublicUrl()` or `$media->getVariantUrl(...)`
 * injects this service.
 *
 * Both methods accept `null` so call sites can fold the old
 * `$entity?->getPublicUrl()` pattern into a single call without
 * re-introducing null-safe checks.
 */
final readonly class MediaUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function publicUrl(?MediaInterface $media): ?string
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->urlGenerator->generate('uploads_serve', ['path' => $media->getPath()]);
    }

    /**
     * Absolute URL flavor for cross-origin contexts (RSS feeds, emails,
     * social sharing, JSON-LD payloads). Same null-safe contract.
     */
    public function publicUrlAbsolute(?MediaInterface $media): ?string
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->urlGenerator->generate(
            'uploads_serve',
            ['path' => $media->getPath()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function variantUrl(?MediaInterface $media, string $variant): ?string
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        $path = $media->getVariantPath($variant);

        return null === $path
            ? null
            : $this->urlGenerator->generate('uploads_serve', ['path' => $path]);
    }

    /**
     * Best variant for thumbnail-size display: tries `medium` first,
     * falls back to `large`, finally to the original. Matches the
     * cascade most consumers were doing inline.
     */
    public function thumbUrl(?MediaInterface $media): ?string
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->variantUrl($media, 'medium')
            ?? $this->variantUrl($media, 'large')
            ?? $this->publicUrl($media);
    }
}
