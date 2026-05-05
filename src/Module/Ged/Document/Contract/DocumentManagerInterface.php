<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Contract;

use Aurora\Module\Ged\Document\DTO\DocumentInput;
use Aurora\Module\Ged\Document\Entity\Document;

interface DocumentManagerInterface
{
    public function create(DocumentInput $input): Document;

    public function update(Document $document, DocumentInput $input): void;

    public function delete(Document $document): void;
}
