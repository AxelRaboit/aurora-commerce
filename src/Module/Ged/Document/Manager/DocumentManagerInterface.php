<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Manager;

use Aurora\Module\Ged\Document\Dto\DocumentInputInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;

interface DocumentManagerInterface
{
    public function create(DocumentInputInterface $input): DocumentInterface;

    public function update(DocumentInterface $document, DocumentInputInterface $input): void;

    public function delete(DocumentInterface $document): void;

    /** @param list<int> $ids */
    public function bulkDelete(array $ids): int;

    /**
     * Crops an image document to a fresh file and records the result as a new
     * version, preserving the pre-crop original. No-op for non-image documents.
     */
    public function cropImage(DocumentInterface $document, int $x, int $y, int $width, int $height): void;
}
