<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInputInterface;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;

interface VaultUserConfigManagerInterface
{
    public function setup(CoreUserInterface $user, VaultUserConfigInputInterface $input): VaultUserConfigInterface;
}
