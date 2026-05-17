<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Platform\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractVaultUserConfig implements VaultUserConfigInterface
{
    use TimestampableTrait;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\Column(length: 128)]
    protected string $argon2Salt;

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getArgon2Salt(): string
    {
        return $this->argon2Salt;
    }

    public function setArgon2Salt(string $argon2Salt): static
    {
        $this->argon2Salt = $argon2Salt;

        return $this;
    }
}
