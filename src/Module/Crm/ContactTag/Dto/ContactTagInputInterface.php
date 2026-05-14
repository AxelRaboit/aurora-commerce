<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Dto;

interface ContactTagInputInterface
{
    public function getLabel(): string;

    public function getSlug(): ?string;

    public function getColor(): string;
}
