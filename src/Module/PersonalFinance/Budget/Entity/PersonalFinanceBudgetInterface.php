<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface PersonalFinanceBudgetInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getMonth(): DateTimeImmutable;

    public function setMonth(DateTimeImmutable $month): static;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;

    /** @return Collection<int, PersonalFinanceBudgetItemInterface> */
    public function getItems(): Collection;
}
