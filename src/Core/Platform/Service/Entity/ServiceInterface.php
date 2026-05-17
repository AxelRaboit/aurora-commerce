<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Service\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;

interface ServiceInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;
}
