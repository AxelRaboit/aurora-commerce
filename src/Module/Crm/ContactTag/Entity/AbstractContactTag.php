<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractContactTag implements ContactTagInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 100, unique: true)]
    protected string $label = '';

    #[ORM\Column(length: 120, unique: true)]
    protected string $slug = '';

    #[ORM\Column(length: 7, options: ['default' => '#6366F1'])]
    protected string $color = '#6366F1';

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
