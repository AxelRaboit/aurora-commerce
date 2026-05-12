<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Manager;

use Aurora\Module\Ged\DocumentFolder\Dto\DocumentFolderInputInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;

interface DocumentFolderManagerInterface
{
    public function create(DocumentFolderInputInterface $input): DocumentFolderInterface;

    public function update(DocumentFolderInterface $folder, DocumentFolderInputInterface $input): void;

    public function delete(DocumentFolderInterface $folder): void;
}
