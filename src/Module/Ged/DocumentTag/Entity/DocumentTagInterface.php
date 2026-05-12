<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Entity;

interface DocumentTagInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getColor(): ?string;

    public function setColor(?string $color): static;
}
