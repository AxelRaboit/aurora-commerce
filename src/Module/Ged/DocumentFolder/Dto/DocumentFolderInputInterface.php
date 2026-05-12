<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Dto;

interface DocumentFolderInputInterface
{
    public function getName(): string;

    public function getParentId(): ?int;

    public function getPosition(): int;
}
