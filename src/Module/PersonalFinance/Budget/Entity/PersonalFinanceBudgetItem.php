<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceBudgetItemRepository::class)]
#[ORM\Table(name: 'core_personal_finance_budget_item')]
#[ORM\Index(name: 'idx_pf_budget_item_budget', columns: ['budget_id'])]
#[ORM\Index(name: 'idx_pf_budget_item_category', columns: ['category_id'])]
class PersonalFinanceBudgetItem extends AbstractPersonalFinanceBudgetItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_budget_item_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
