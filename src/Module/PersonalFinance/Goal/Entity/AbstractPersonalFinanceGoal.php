<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Goal\Enum\PersonalFinanceGoalTrackingModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceGoal implements PersonalFinanceGoalInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceWalletInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?PersonalFinanceWalletInterface $wallet = null;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceCategoryInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?PersonalFinanceCategoryInterface $category = null;

    #[ORM\Column(length: 120)]
    protected string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected string $targetAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    protected string $savedAmount = '0.00';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?DateTimeImmutable $deadline = null;

    #[ORM\Column(length: 7, nullable: true)]
    protected ?string $color = null;

    /**
     * Drives how the auto-sync subscriber sums transactions onto
     * `savedAmount`. Ignored when `category` is null (manual deposit
     * mode). Defaults to `ExpenseOnly` — the most common case
     * ("budget consumed" goals).
     */
    #[ORM\Column(length: 16, enumType: PersonalFinanceGoalTrackingModeEnum::class, options: ['default' => 'expense_only'])]
    protected PersonalFinanceGoalTrackingModeEnum $trackingMode = PersonalFinanceGoalTrackingModeEnum::ExpenseOnly;

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWallet(): ?PersonalFinanceWalletInterface
    {
        return $this->wallet;
    }

    public function setWallet(?PersonalFinanceWalletInterface $wallet): static
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTargetAmount(): string
    {
        return $this->targetAmount;
    }

    public function setTargetAmount(string $targetAmount): static
    {
        $this->targetAmount = $targetAmount;

        return $this;
    }

    public function getSavedAmount(): string
    {
        return $this->savedAmount;
    }

    public function setSavedAmount(string $savedAmount): static
    {
        $this->savedAmount = $savedAmount;

        return $this;
    }

    public function getDeadline(): ?DateTimeImmutable
    {
        return $this->deadline;
    }

    public function setDeadline(?DateTimeImmutable $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getTrackingMode(): PersonalFinanceGoalTrackingModeEnum
    {
        return $this->trackingMode;
    }

    public function setTrackingMode(PersonalFinanceGoalTrackingModeEnum $trackingMode): static
    {
        $this->trackingMode = $trackingMode;

        return $this;
    }

    public function getProgress(): float
    {
        $target = (float) $this->targetAmount;
        if ($target <= 0.0) {
            return 0.0;
        }

        return min(100.0, ((float) $this->savedAmount / $target) * 100.0);
    }

    public function isCompleted(): bool
    {
        return 1 !== bccomp($this->targetAmount, $this->savedAmount, 2);
    }

    public function isAutoTracked(): bool
    {
        return $this->category instanceof PersonalFinanceCategoryInterface;
    }
}
