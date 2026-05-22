<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Entity;

use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceCategoryRepository::class)]
#[ORM\Table(name: 'core_personal_finance_category')]
#[ORM\Index(name: 'idx_pf_category_wallet', columns: ['wallet_id'])]
class PersonalFinanceCategory extends AbstractPersonalFinanceCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_personal_finance_category_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
