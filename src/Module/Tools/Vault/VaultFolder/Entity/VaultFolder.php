<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultFolder\Entity;

use Aurora\Module\Tools\Vault\VaultFolder\Repository\VaultFolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VaultFolderRepository::class)]
#[ORM\Table(name: 'core_vault_folders')]
class VaultFolder extends AbstractVaultFolder
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_vault_folder_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, VaultFolderInterface> */
    #[ORM\OneToMany(targetEntity: VaultFolderInterface::class, mappedBy: 'parent')]
    protected Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
