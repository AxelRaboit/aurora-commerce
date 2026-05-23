<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Goal\Enum\PersonalFinanceGoalTrackingModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceGoalInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getWallet(): ?PersonalFinanceWalletInterface;

    public function setWallet(?PersonalFinanceWalletInterface $wallet): static;

    public function getCategory(): ?PersonalFinanceCategoryInterface;

    public function setCategory(?PersonalFinanceCategoryInterface $category): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getTargetAmount(): string;

    public function setTargetAmount(string $targetAmount): static;

    public function getSavedAmount(): string;

    public function setSavedAmount(string $savedAmount): static;

    public function getDeadline(): ?DateTimeImmutable;

    public function setDeadline(?DateTimeImmutable $deadline): static;

    public function getColor(): ?string;

    public function setColor(?string $color): static;

    public function getTrackingMode(): PersonalFinanceGoalTrackingModeEnum;

    public function setTrackingMode(PersonalFinanceGoalTrackingModeEnum $trackingMode): static;

    /** Percentage [0.0, 100.0]. */
    public function getProgress(): float;

    public function isCompleted(): bool;

    public function isAutoTracked(): bool;
}
