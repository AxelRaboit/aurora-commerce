<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\Entity;

interface ThemeInterface
{
    public function getId(): ?int;

    public function getSlug(): string;

    public function setSlug(string $slug): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function isActive(): bool;

    public function setActive(bool $active): static;

    /** @return array<string, mixed> */
    public function getConfig(): array;

    /** @param array<string, mixed> $config */
    public function setConfig(array $config): static;
}
