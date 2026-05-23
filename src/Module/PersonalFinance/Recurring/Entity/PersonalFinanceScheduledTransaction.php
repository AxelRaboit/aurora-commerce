<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Entity;

use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceScheduledTransactionRepository::class)]
#[ORM\Table(name: 'core_personal_finance_scheduled_tx')]
#[ORM\Index(name: 'idx_pf_scheduled_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_pf_scheduled_wallet', columns: ['wallet_id'])]
#[ORM\Index(name: 'idx_pf_scheduled_date', columns: ['scheduled_date'])]
class PersonalFinanceScheduledTransaction extends AbstractPersonalFinanceScheduledTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_scheduled_tx_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
