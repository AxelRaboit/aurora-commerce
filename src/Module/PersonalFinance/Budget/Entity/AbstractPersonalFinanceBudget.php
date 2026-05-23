<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceBudget implements PersonalFinanceBudgetInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceWalletInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PersonalFinanceWalletInterface $wallet;

    #[ORM\Column(type: 'date_immutable')]
    protected DateTimeImmutable $month;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $notes = null;

    /** @var Collection<int, PersonalFinanceBudgetItemInterface> */
    #[ORM\OneToMany(targetEntity: PersonalFinanceBudgetItemInterface::class, mappedBy: 'budget', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

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

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function setMonth(DateTimeImmutable $month): static
    {
        $this->month = $month->modify('first day of this month')->setTime(0, 0);

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /** @return Collection<int, PersonalFinanceBudgetItemInterface> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
