<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Dto;

interface VaultFolderInputInterface
{
    public function getName(): string;

    public function getColor(): ?string;

    public function getPosition(): int;

    public function getParentId(): ?int;
}
