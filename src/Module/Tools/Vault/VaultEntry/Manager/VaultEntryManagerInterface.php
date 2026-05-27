<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultEntry\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Tools\Vault\VaultEntry\Dto\VaultEntryInputInterface;
use Aurora\Module\Tools\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Tools\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultEntryManagerInterface
{
    public function create(CoreUserInterface $user, VaultEntryInputInterface $input): VaultEntryInterface;

    public function update(VaultEntryInterface $entry, VaultEntryInputInterface $input): void;

    public function delete(VaultEntryInterface $entry): void;

    public function toggleFavorite(VaultEntryInterface $entry): void;

    public function move(VaultEntryInterface $entry, ?VaultFolderInterface $folder): void;
}
