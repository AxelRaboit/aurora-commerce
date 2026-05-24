<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class Abstract{{NAME}} implements {{NAME}}Interface
{
    use TimestampableTrait;

    #[ORM\Column(length: 150)]
    protected string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
