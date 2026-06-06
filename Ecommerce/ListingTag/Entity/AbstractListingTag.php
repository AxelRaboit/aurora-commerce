<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractListingTag implements ListingTagInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 7, options: ['default' => '#6366F1'])]
    protected string $color = '#6366F1';

    #[ORM\Column(options: ['default' => true])]
    protected bool $isVisible = true;

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setVisible(bool $visible): static
    {
        $this->isVisible = $visible;

        return $this;
    }
}
