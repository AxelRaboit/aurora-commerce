<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Dto;

interface ThemeInputInterface
{
    public function getSlug(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    /** @return array<string, mixed> */
    public function getConfig(): array;
}
