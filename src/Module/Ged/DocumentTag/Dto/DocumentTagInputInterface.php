<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Dto;

interface DocumentTagInputInterface
{
    public function getName(): string;

    public function getColor(): ?string;
}
