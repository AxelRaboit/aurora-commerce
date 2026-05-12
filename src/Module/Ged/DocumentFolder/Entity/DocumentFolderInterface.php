<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Entity;

use Doctrine\Common\Collections\Collection;

interface DocumentFolderInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getParent(): ?DocumentFolderInterface;

    public function setParent(?DocumentFolderInterface $parent): static;

    /** @return Collection<int, DocumentFolderInterface> */
    public function getChildren(): Collection;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
