<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Entity;

use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalFinanceCategoryRepository::class)]
#[ORM\Table(name: 'core_personal_finance_category')]
#[ORM\Index(name: 'idx_pf_category_wallet', columns: ['wallet_id'])]
// Partial unique index on (wallet_id, system_key) WHERE system_key IS
// NOT NULL — declared as UniqueConstraint with options.where so
// doctrine:schema:validate matches it against the raw-SQL index
// created in migrations/Version20260522184617. The sibling
// uniq_pf_category_user_name index uses LOWER(name) which Doctrine's
// mapping can't express (no functional columns), so it remains
// invisible to the validator — accepted trade-off.
#[ORM\UniqueConstraint(
    name: 'uniq_pf_category_system_key',
    columns: ['wallet_id', 'system_key'],
    options: ['where' => '(system_key IS NOT NULL)'],
)]
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
