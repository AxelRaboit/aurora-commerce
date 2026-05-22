<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Collection;

interface PersonalFinanceWalletInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getOwner(): CoreUserInterface;

    public function setOwner(CoreUserInterface $owner): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getStartBalance(): string;

    public function setStartBalance(string $startBalance): static;

    public function getMode(): PersonalFinanceWalletModeEnum;

    public function setMode(PersonalFinanceWalletModeEnum $mode): static;

    public function isShowOnDashboard(): bool;

    public function setShowOnDashboard(bool $showOnDashboard): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function isBudgetMode(): bool;

    public function isSimpleMode(): bool;

    /** @return Collection<int, PersonalFinanceWalletMemberInterface> */
    public function getMembers(): Collection;

    public function roleFor(CoreUserInterface $user): ?PersonalFinanceWalletRoleEnum;

    public function isShared(): bool;
}
