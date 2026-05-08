<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Entity;

interface LocaleInterface
{
    public function getCode(): string;

    public function setCode(string $code): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function isDefault(): bool;

    public function setIsDefault(bool $isDefault): static;

    public function isActive(): bool;

    public function setIsActive(bool $isActive): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
