<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Entity;

use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceWalletMemberRepository::class)]
#[ORM\Table(name: 'core_personal_finance_wallet_member')]
#[ORM\UniqueConstraint(name: 'uniq_pf_wallet_member', columns: ['wallet_id', 'user_id'])]
class PersonalFinanceWalletMember extends AbstractPersonalFinanceWalletMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_wallet_member_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
