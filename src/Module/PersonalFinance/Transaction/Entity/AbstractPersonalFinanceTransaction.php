<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceTransaction implements PersonalFinanceTransactionInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceWalletInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PersonalFinanceWalletInterface $wallet;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceCategoryInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?PersonalFinanceCategoryInterface $category = null;

    #[ORM\Column(length: 16, enumType: PersonalFinanceTransactionTypeEnum::class)]
    protected PersonalFinanceTransactionTypeEnum $type;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected string $amount;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'date_immutable')]
    protected DateTimeImmutable $date;

    /** @var list<string> */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $tags = null;

    #[ORM\Column(length: 36, nullable: true)]
    protected ?string $transferId = null;

    #[ORM\Column(length: 36, nullable: true)]
    protected ?string $splitId = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $attachmentPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $attachmentOriginalName = null;

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getCategory(): ?PersonalFinanceCategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?PersonalFinanceCategoryInterface $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getType(): PersonalFinanceTransactionTypeEnum
    {
        return $this->type;
    }

    public function setType(PersonalFinanceTransactionTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    /** @param list<string> $tags */
    public function setTags(array $tags): static
    {
        $this->tags = [] === $tags ? null : $tags;

        return $this;
    }

    public function getTransferId(): ?string
    {
        return $this->transferId;
    }

    public function setTransferId(?string $transferId): static
    {
        $this->transferId = $transferId;

        return $this;
    }

    public function getSplitId(): ?string
    {
        return $this->splitId;
    }

    public function setSplitId(?string $splitId): static
    {
        $this->splitId = $splitId;

        return $this;
    }

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function setAttachmentPath(?string $attachmentPath): static
    {
        $this->attachmentPath = $attachmentPath;

        return $this;
    }

    public function getAttachmentOriginalName(): ?string
    {
        return $this->attachmentOriginalName;
    }

    public function setAttachmentOriginalName(?string $attachmentOriginalName): static
    {
        $this->attachmentOriginalName = $attachmentOriginalName;

        return $this;
    }

    public function hasAttachment(): bool
    {
        return null !== $this->attachmentPath;
    }

    public function isIncome(): bool
    {
        return PersonalFinanceTransactionTypeEnum::Income === $this->type;
    }

    public function isExpense(): bool
    {
        return PersonalFinanceTransactionTypeEnum::Expense === $this->type;
    }

    public function isTransfer(): bool
    {
        return null !== $this->transferId;
    }

    public function isSplit(): bool
    {
        return null !== $this->splitId;
    }
}
