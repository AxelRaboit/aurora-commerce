<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Entity;

use Aurora\Core\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractResetPasswordRequest implements ResetPasswordRequestInterface
{
    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(length: 100)]
    protected string $selector;

    #[ORM\Column(length: 100)]
    protected string $hashedToken;

    #[ORM\Column]
    protected DateTimeImmutable $expiresAt;

    public function __construct(User $user, string $selector, string $hashedToken, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->expiresAt = $expiresAt;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
