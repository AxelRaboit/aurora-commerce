<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractDocumentTag implements DocumentTagInterface
{
    use TimestampableTrait;

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
