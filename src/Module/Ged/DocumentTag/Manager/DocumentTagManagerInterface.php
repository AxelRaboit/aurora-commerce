<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Manager;

use Aurora\Module\Ged\DocumentTag\Dto\DocumentTagInputInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;

interface DocumentTagManagerInterface
{
    public function create(DocumentTagInputInterface $input): DocumentTagInterface;

    public function update(DocumentTagInterface $tag, DocumentTagInputInterface $input): void;

    public function delete(DocumentTagInterface $tag): void;
}
