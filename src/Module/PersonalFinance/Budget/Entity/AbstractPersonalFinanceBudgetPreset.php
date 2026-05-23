<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceBudgetPreset implements PersonalFinanceBudgetPresetInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceWalletInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PersonalFinanceWalletInterface $wallet;

    #[ORM\Column(length: 120)]
    protected string $name;

    #[ORM\Column(length: 500, nullable: true)]
    protected ?string $description = null;

    /** @var Collection<int, PersonalFinanceBudgetPresetItemInterface> */
    #[ORM\OneToMany(targetEntity: PersonalFinanceBudgetPresetItemInterface::class, mappedBy: 'preset', cascade: ['persist', 'remove'], orphanRemoval: true)]
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /** @return Collection<int, PersonalFinanceBudgetPresetItemInterface> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
