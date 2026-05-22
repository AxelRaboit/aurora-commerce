<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceWallet implements PersonalFinanceWalletInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $owner;

    #[ORM\Column(length: 120)]
    protected string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    protected string $startBalance = '0.00';

    #[ORM\Column(length: 16, enumType: PersonalFinanceWalletModeEnum::class)]
    protected PersonalFinanceWalletModeEnum $mode = PersonalFinanceWalletModeEnum::Simple;

    #[ORM\Column(options: ['default' => true])]
    protected bool $showOnDashboard = true;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    /** @var Collection<int, PersonalFinanceWalletMemberInterface> */
    #[ORM\OneToMany(targetEntity: PersonalFinanceWalletMemberInterface::class, mappedBy: 'wallet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function roleFor(CoreUserInterface $user): ?PersonalFinanceWalletRoleEnum
    {
        foreach ($this->members as $member) {
            if ($member->getUser()->getId() === $user->getId()) {
                return $member->getRole();
            }
        }

        return null;
    }

    public function isShared(): bool
    {
        return $this->members->count() > 1;
    }

    public function getOwner(): CoreUserInterface
    {
        return $this->owner;
    }

    public function setOwner(CoreUserInterface $owner): static
    {
        $this->owner = $owner;

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

    public function getStartBalance(): string
    {
        return $this->startBalance;
    }

    public function setStartBalance(string $startBalance): static
    {
        $this->startBalance = $startBalance;

        return $this;
    }

    public function getMode(): PersonalFinanceWalletModeEnum
    {
        return $this->mode;
    }

    public function setMode(PersonalFinanceWalletModeEnum $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function isShowOnDashboard(): bool
    {
        return $this->showOnDashboard;
    }

    public function setShowOnDashboard(bool $showOnDashboard): static
    {
        $this->showOnDashboard = $showOnDashboard;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isBudgetMode(): bool
    {
        return PersonalFinanceWalletModeEnum::Budget === $this->mode;
    }

    public function isSimpleMode(): bool
    {
        return PersonalFinanceWalletModeEnum::Simple === $this->mode;
    }
}
