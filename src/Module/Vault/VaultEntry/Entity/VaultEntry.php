<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Entity;

use Aurora\Module\Vault\VaultEntry\Repository\VaultEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VaultEntryRepository::class)]
#[ORM\Table(name: 'core_vault_entries')]
class VaultEntry extends AbstractVaultEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_vault_entry_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
