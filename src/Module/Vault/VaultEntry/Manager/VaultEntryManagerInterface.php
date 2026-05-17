<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Manager;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultEntry\Dto\VaultEntryInputInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultEntryManagerInterface
{
    public function create(CoreUserInterface $user, VaultEntryInputInterface $input): VaultEntryInterface;

    public function update(VaultEntryInterface $entry, VaultEntryInputInterface $input): void;

    public function delete(VaultEntryInterface $entry): void;

    public function toggleFavorite(VaultEntryInterface $entry): void;

    public function move(VaultEntryInterface $entry, ?VaultFolderInterface $folder): void;
}
