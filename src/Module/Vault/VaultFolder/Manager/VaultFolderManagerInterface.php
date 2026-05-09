<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultFolder\Dto\VaultFolderInputInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultFolderManagerInterface
{
    public function create(CoreUserInterface $user, VaultFolderInputInterface $input): VaultFolderInterface;

    public function update(VaultFolderInterface $folder, VaultFolderInputInterface $input): void;

    public function delete(VaultFolderInterface $folder): void;
}
