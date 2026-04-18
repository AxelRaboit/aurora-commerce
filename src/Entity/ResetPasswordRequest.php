<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: 'reset_password_requests')]
class ResetPasswordRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(length: 100)]
        private string $selector,
        #[ORM\Column(length: 100)]
        private string $hashedToken,
        #[ORM\Column]
        private DateTimeImmutable $expiresAt,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
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
