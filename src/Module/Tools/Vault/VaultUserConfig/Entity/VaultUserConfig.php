<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Entity;

use Aurora\Module\Tools\Vault\VaultUserConfig\Repository\VaultUserConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VaultUserConfigRepository::class)]
#[ORM\Table(name: 'core_vault_user_configs')]
class VaultUserConfig extends AbstractVaultUserConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_vault_user_config_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
