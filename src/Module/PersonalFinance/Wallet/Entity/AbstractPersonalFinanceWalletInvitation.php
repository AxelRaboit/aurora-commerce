<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractPersonalFinanceWalletInvitation implements PersonalFinanceWalletInvitationInterface
{
    #[ORM\ManyToOne(targetEntity: PersonalFinanceWalletInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PersonalFinanceWalletInterface $wallet;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $invitedBy;

    #[ORM\Column(length: 180)]
    protected string $email;

    #[ORM\Column(length: 16, enumType: PersonalFinanceWalletRoleEnum::class)]
    protected PersonalFinanceWalletRoleEnum $role;

    #[ORM\Column(length: 64, unique: true)]
    protected string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $declinedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getWallet(): PersonalFinanceWalletInterface
    {
        return $this->wallet;
    }

    public function setWallet(PersonalFinanceWalletInterface $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getInvitedBy(): CoreUserInterface
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(CoreUserInterface $invitedBy): static
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): PersonalFinanceWalletRoleEnum
    {
        return $this->role;
    }

    public function setRole(PersonalFinanceWalletRoleEnum $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAcceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getDeclinedAt(): ?DateTimeImmutable
    {
        return $this->declinedAt;
    }

    public function setDeclinedAt(?DateTimeImmutable $declinedAt): static
    {
        $this->declinedAt = $declinedAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isAccepted(): bool
    {
        return $this->acceptedAt instanceof DateTimeImmutable;
    }

    public function isDeclined(): bool
    {
        return $this->declinedAt instanceof DateTimeImmutable;
    }

    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isDeclined() && !$this->isExpired();
    }
}
