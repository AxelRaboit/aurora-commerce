<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Entity;

use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceCategorizationRuleRepository::class)]
#[ORM\Table(name: 'core_personal_finance_categorization_rule')]
#[ORM\UniqueConstraint(name: 'uniq_pf_categ_rule_user_pattern', columns: ['user_id', 'pattern'])]
#[ORM\Index(name: 'idx_pf_categ_rule_pattern', columns: ['pattern'])]
#[ORM\Index(name: 'idx_pf_categ_rule_category', columns: ['category_id'])]
class PersonalFinanceCategorizationRule extends AbstractPersonalFinanceCategorizationRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_categorization_rule_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
