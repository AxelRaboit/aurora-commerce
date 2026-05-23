<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetPresetItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceBudgetPresetItemRepository::class)]
#[ORM\Table(name: 'core_personal_finance_budget_preset_item')]
#[ORM\Index(name: 'idx_pf_budget_preset_item_preset', columns: ['preset_id'])]
class PersonalFinanceBudgetPresetItem extends AbstractPersonalFinanceBudgetPresetItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_budget_preset_item_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
