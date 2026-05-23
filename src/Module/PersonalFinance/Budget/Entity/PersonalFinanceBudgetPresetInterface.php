<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Collection;

interface PersonalFinanceBudgetPresetInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    /** @return Collection<int, PersonalFinanceBudgetPresetItemInterface> */
    public function getItems(): Collection;
}
