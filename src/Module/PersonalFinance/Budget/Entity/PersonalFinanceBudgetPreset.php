<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetPresetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceBudgetPresetRepository::class)]
#[ORM\Table(name: 'core_personal_finance_budget_preset')]
#[ORM\Index(name: 'idx_pf_budget_preset_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_pf_budget_preset_wallet', columns: ['wallet_id'])]
class PersonalFinanceBudgetPreset extends AbstractPersonalFinanceBudgetPreset
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_budget_preset_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
