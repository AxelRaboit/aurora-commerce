<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Core\User\Entity\CoreUserInterface;

interface VaultUserConfigInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getArgon2Salt(): string;

    public function setArgon2Salt(string $argon2Salt): static;
}
