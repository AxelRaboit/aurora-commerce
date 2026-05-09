<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Dto;

use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;

interface VaultEntryInputInterface
{
    public function getType(): VaultRecordTypeEnum;

    public function getTitle(): string;

    public function getUrl(): ?string;

    public function getEncryptedData(): string;

    public function getIv(): string;

    public function getFolderId(): ?int;

    public function isFavorite(): bool;
}
