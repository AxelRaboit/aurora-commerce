<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\CoreUserInterface;

interface VaultFolderInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getColor(): ?string;

    public function setColor(?string $color): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getParent(): ?VaultFolderInterface;

    public function setParent(?VaultFolderInterface $parent): static;
}
