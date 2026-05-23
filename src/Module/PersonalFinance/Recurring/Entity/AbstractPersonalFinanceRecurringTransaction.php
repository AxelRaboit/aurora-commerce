<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceRecurringTransaction implements PersonalFinanceRecurringTransactionInterface
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

    /**
     * Capped to 1..28 (enforced by a CHECK constraint added in the
     * migration) so that no month is ever silently skipped — Spendly
     * makes the same choice (only 28 days are guaranteed to exist
     * across every month).
     */
    #[ORM\Column(type: 'smallint')]
    protected int $dayOfMonth = 1;

    #[ORM\Column(options: ['default' => true])]
    protected bool $active = true;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?DateTimeImmutable $lastGeneratedAt = null;

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

    public function getDayOfMonth(): int
    {
        return $this->dayOfMonth;
    }

    public function setDayOfMonth(int $day): static
    {
        if ($day < 1 || $day > 28) {
            throw new InvalidArgumentException(sprintf('dayOfMonth must be in [1, 28], got %d.', $day));
        }

        $this->dayOfMonth = $day;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getLastGeneratedAt(): ?DateTimeImmutable
    {
        return $this->lastGeneratedAt;
    }

    public function setLastGeneratedAt(?DateTimeImmutable $lastGeneratedAt): static
    {
        $this->lastGeneratedAt = $lastGeneratedAt;

        return $this;
    }
}
