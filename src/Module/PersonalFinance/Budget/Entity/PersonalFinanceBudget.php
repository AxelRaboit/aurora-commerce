<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceBudgetRepository::class)]
#[ORM\Table(name: 'core_personal_finance_budget')]
#[ORM\UniqueConstraint(name: 'uniq_pf_budget_wallet_month', columns: ['wallet_id', 'month'])]
#[ORM\Index(name: 'idx_pf_budget_user', columns: ['user_id'])]
class PersonalFinanceBudget extends AbstractPersonalFinanceBudget
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_budget_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
