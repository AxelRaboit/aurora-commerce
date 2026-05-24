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
}
