<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategoryInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function isSystem(): bool;

    public function setIsSystem(bool $isSystem): static;

    public function getSystemKey(): ?string;

    public function setSystemKey(?string $systemKey): static;
}
