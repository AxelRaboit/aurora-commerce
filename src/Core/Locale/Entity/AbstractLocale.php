<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractLocale implements LocaleInterface
{
    #[ORM\Column(length: 100)]
    protected string $name;

    #[ORM\Column]
    protected bool $isDefault = false;

    #[ORM\Column]
    protected bool $isActive = true;

    #[ORM\Column]
    protected int $position = 0;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
