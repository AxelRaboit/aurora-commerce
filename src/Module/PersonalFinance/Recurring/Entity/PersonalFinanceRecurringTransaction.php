<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Entity;

use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceRecurringTransactionRepository::class)]
#[ORM\Table(name: 'core_personal_finance_recurring_tx')]
#[ORM\Index(name: 'idx_pf_recurring_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_pf_recurring_wallet', columns: ['wallet_id'])]
#[ORM\Index(name: 'idx_pf_recurring_active_day', columns: ['active', 'day_of_month'])]
class PersonalFinanceRecurringTransaction extends AbstractPersonalFinanceRecurringTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_recurring_tx_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
