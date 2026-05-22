<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Entity;

use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceTransactionRepository::class)]
#[ORM\Table(name: 'core_personal_finance_transaction')]
#[ORM\Index(name: 'idx_pf_transaction_wallet_date', columns: ['wallet_id', 'date'])]
#[ORM\Index(name: 'idx_pf_transaction_user_date', columns: ['user_id', 'date'])]
#[ORM\Index(name: 'idx_pf_transaction_category_date', columns: ['category_id', 'date'])]
#[ORM\Index(name: 'idx_pf_transaction_transfer', columns: ['transfer_id'])]
#[ORM\Index(name: 'idx_pf_transaction_split', columns: ['split_id'])]
class PersonalFinanceTransaction extends AbstractPersonalFinanceTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_transaction_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
