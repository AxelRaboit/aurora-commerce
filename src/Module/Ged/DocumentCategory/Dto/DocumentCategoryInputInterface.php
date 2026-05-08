<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Dto;

interface DocumentCategoryInputInterface
{
    public function getName(): string;

    public function getDescription(): ?string;
}
