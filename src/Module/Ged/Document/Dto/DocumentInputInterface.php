<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Dto;

use Aurora\Module\Ged\Enum\DocumentStatusEnum;

interface DocumentInputInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getStatus(): DocumentStatusEnum;

    public function getCategoryId(): ?int;

    public function getFileId(): ?int;
}
