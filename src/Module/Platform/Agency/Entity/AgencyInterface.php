<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Agency\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;

interface AgencyInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;
}
