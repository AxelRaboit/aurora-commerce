<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Doctrine\Common\Collections\Collection;

interface MediaFolderInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getParent(): ?MediaFolderInterface;

    public function setParent(?MediaFolderInterface $parent): static;

    /** @return Collection<int, MediaFolderInterface> */
    public function getChildren(): Collection;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    /** @return list<MediaFolderInterface> */
    public function getAncestors(): array;

    public function isDescendantOf(MediaFolderInterface $candidate): bool;
}
