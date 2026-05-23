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
// One auto-tracked goal per (user, category, wallet) — wallet=NULL is a
// distinct slot (the cross-wallet variant) so users can have BOTH "Food
// global" and "Food on Wallet A" living side by side. Postgres treats
// NULLs as distinct in standard unique indexes, so the COALESCE(wallet_id, 0)
// trick collapses them into a single slot. Defined via a raw migration —
// Doctrine's UniqueConstraint attribute can't express the COALESCE
// expression natively.
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
