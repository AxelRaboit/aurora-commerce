<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractDocumentTag implements DocumentTagInterface
{
    #[ORM\Column(length: 100)]
    protected string $name;

    #[ORM\Column(length: 7, nullable: true)]
    protected ?string $color = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
