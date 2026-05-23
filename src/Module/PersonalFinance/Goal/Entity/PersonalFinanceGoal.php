<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Entity;

use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceGoalRepository::class)]
#[ORM\Table(name: 'core_personal_finance_goal')]
#[ORM\Index(name: 'idx_pf_goal_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_pf_goal_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_pf_goal_wallet', columns: ['wallet_id'])]
// Partial unique index on (user_id, category_id) so a category can
// drive at most one auto-tracked goal per user. The DB-level guarantee
// lets PersonalFinanceGoalRepository::findByCategory return at most one
// row, mirroring Spendly's TransactionObserver which used ->first().
#[ORM\UniqueConstraint(
    name: 'uniq_pf_goal_user_category',
    columns: ['user_id', 'category_id'],
    options: ['where' => '(category_id IS NOT NULL)'],
)]
class PersonalFinanceGoal extends AbstractPersonalFinanceGoal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_goal_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
