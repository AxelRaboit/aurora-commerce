<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Search;

use Aurora\Core\Search\BackendSearchProviderInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;

/**
 * Ged (media library) slice of the backend global search: documents by name.
 * Ged ships in core, but contributing through the same provider registry keeps
 * the General search controller a pure aggregator with no domain knowledge.
 */
final readonly class GedBackendSearchProvider implements BackendSearchProviderInterface
{
    public function __construct(
        private DocumentRepository $documentRepository,
    ) {}

    public function search(string $query): array
    {
        $mediaSerialized = array_map(
            static fn (DocumentInterface $document): array => [
                'id' => $document->getId(),
                'name' => $document->getOriginalName() ?? $document->getTitle(),
                'mimeType' => $document->getMimeType(),
                'alt' => $document->getAlt(),
            ],
            $this->documentRepository->searchByName($query, 10),
        );

        return [
            'media' => $mediaSerialized,
        ];
    }
}
