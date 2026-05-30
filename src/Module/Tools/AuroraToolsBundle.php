<?php

declare(strict_types=1);

namespace Aurora\Module\Tools;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;

/**
 * Self-contained bundle for the Tools module (Vault + PasswordGenerator).
 *
 * POC for the monorepo split: registers Tools' Doctrine mapping, Twig
 * namespace, translations and entity resolution on its own — AuroraBundle no
 * longer knows about Tools (it is excluded from the central glob). In the
 * target topology this class + the module dir ship as `axelraboit/aurora-tools`;
 * a client installs it by adding the package and this bundle, and gets only
 * Tools. Removing it removes the module entirely.
 */
final class AuroraToolsBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Tools';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            VaultEntryInterface::class => VaultEntry::class,
            VaultFolderInterface::class => VaultFolder::class,
            VaultUserConfigInterface::class => VaultUserConfig::class,
        ];
    }
}
