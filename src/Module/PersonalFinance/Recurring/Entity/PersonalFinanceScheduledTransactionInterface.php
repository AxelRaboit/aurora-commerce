<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceScheduledTransactionInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getWallet(): PersonalFinanceWalletInterface;

    public function setWallet(PersonalFinanceWalletInterface $wallet): static;

    public function getCategory(): ?PersonalFinanceCategoryInterface;

    public function setCategory(?PersonalFinanceCategoryInterface $category): static;

    public function getType(): PersonalFinanceTransactionTypeEnum;

    public function setType(PersonalFinanceTransactionTypeEnum $type): static;

    public function getAmount(): string;

    public function setAmount(string $amount): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getScheduledDate(): DateTimeImmutable;

    public function setScheduledDate(DateTimeImmutable $scheduledDate): static;

    public function isGenerated(): bool;

    public function setGenerated(bool $generated): static;
}
