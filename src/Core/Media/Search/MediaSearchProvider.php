<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Search;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Search\Provider\SearchProviderInterface;
use Aurora\Core\User\Entity\CoreUserInterface;

use function sprintf;

final readonly class MediaSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private MediaRepository $mediaRepository,
    ) {}

    public function search(string $query, int $limit, CoreUserInterface $user): array
    {
        $lines = [];
        foreach ($this->mediaRepository->searchByName($query, $limit) as $media) {
            $lines[] = sprintf('[MEDIA #%d] %s (%s)', $media->getId(), (string) $media->getOriginalName(), (string) $media->getMimeType());
        }

        return $lines;
    }
}
