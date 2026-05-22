<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceWalletInvitationRepository::class)]
#[ORM\Table(name: 'core_personal_finance_wallet_invitation')]
#[ORM\UniqueConstraint(name: 'uniq_pf_wallet_invitation_email', columns: ['wallet_id', 'email'])]
class PersonalFinanceWalletInvitation extends AbstractPersonalFinanceWalletInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_wallet_invitation_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
