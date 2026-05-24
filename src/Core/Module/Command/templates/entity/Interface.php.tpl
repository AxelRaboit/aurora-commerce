<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;

interface {{NAME}}Interface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;
}
