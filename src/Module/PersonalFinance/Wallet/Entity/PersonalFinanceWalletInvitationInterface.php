<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceWalletInvitationInterface
{
    public function getId(): ?int;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getInvitedBy(): CoreUserInterface;

    public function setInvitedBy(CoreUserInterface $invitedBy): static;

    public function getEmail(): string;

    public function setEmail(string $email): static;

    public function getRole(): PersonalFinanceWalletRoleEnum;

    public function setRole(PersonalFinanceWalletRoleEnum $role): static;

    public function getToken(): string;

    public function setToken(string $token): static;

    public function getExpiresAt(): DateTimeImmutable;

    public function setExpiresAt(DateTimeImmutable $expiresAt): static;

    public function getAcceptedAt(): ?DateTimeImmutable;

    public function setAcceptedAt(?DateTimeImmutable $acceptedAt): static;

    public function getDeclinedAt(): ?DateTimeImmutable;

    public function setDeclinedAt(?DateTimeImmutable $declinedAt): static;

    public function getCreatedAt(): DateTimeImmutable;

    public function isExpired(): bool;

    public function isAccepted(): bool;

    public function isDeclined(): bool;

    public function isPending(): bool;
}
