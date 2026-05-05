<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Contract;

use Aurora\Module\Ged\DocumentCategory\DTO\DocumentCategoryInput;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;

interface DocumentCategoryManagerInterface
{
    public function create(DocumentCategoryInput $input): DocumentCategory;

    public function update(DocumentCategory $category, DocumentCategoryInput $input): void;

    public function delete(DocumentCategory $category): void;
}
