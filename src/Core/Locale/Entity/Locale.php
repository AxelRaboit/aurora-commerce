<?php

declare(strict_types=1);

namespace App\Core\Locale\Entity;

use App\Core\Locale\Repository\LocaleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocaleRepository::class)]
#[ORM\Table(name: 'locales')]
class Locale
{
    #[ORM\Id]
    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $position = 0;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

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
