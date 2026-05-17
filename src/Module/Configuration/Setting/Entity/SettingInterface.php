<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Entity;

interface SettingInterface
{
    public function getKey(): string;

    public function setKey(string $key): static;

    public function getValue(): ?string;

    public function setValue(?string $value): static;

    public function getCastedValue(): mixed;

    public function getType(): string;

    public function setType(string $type): static;

    public function getGroup(): ?string;

    public function setGroup(?string $group): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;
}
