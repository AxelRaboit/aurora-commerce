<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceTransactionInterface extends TimestampableInterface
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

    public function getDate(): DateTimeImmutable;

    public function setDate(DateTimeImmutable $date): static;

    /** @return list<string> */
    public function getTags(): array;

    /** @param list<string> $tags */
    public function setTags(array $tags): static;

    public function getTransferId(): ?string;

    public function setTransferId(?string $transferId): static;

    public function getSplitId(): ?string;

    public function setSplitId(?string $splitId): static;

    public function getAttachmentPath(): ?string;

    public function setAttachmentPath(?string $attachmentPath): static;

    public function isIncome(): bool;

    public function isExpense(): bool;

    public function isTransfer(): bool;

    public function isSplit(): bool;
}
