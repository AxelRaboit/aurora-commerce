<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Entity;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Collection;

interface BlockNoteInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getAgency(): ?AgencyInterface;

    public function setAgency(?AgencyInterface $agency): static;

    public function getParent(): ?self;

    public function setParent(?self $parent): static;

    /** @return Collection<int, BlockNoteInterface> */
    public function getChildren(): Collection;

    public function getTitle(): ?string;

    public function setTitle(?string $title): static;

    /** @return list<array{id?: string, type: string, data: array<string, mixed>}> */
    public function getBlocks(): array;

    /** @param list<array{id?: string, type: string, data: array<string, mixed>}> $blocks */
    public function setBlocks(array $blocks): static;

    /** @return list<string> */
    public function getTags(): array;

    /** @param list<string> $tags */
    public function setTags(array $tags): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
