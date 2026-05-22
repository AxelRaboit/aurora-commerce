<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceWalletMemberInterface
{
    public function getId(): ?int;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getRole(): PersonalFinanceWalletRoleEnum;

    public function setRole(PersonalFinanceWalletRoleEnum $role): static;

    public function getCreatedAt(): DateTimeImmutable;
}
