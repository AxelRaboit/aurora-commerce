<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Manager;

use Aurora\Module\Ged\DocumentCategory\Dto\DocumentCategoryInputInterface;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;

interface DocumentCategoryManagerInterface
{
    public function create(DocumentCategoryInputInterface $input): DocumentCategoryInterface;

    public function update(DocumentCategoryInterface $category, DocumentCategoryInputInterface $input): void;

    public function delete(DocumentCategoryInterface $category): void;
}
