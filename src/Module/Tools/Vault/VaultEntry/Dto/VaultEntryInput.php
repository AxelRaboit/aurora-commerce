<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultEntry\Dto;

use Aurora\Module\Tools\Vault\Enum\VaultRecordTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class VaultEntryInput implements VaultEntryInputInterface
{
    public function __construct(
        #[Assert\NotNull]
        public readonly VaultRecordTypeEnum $type = VaultRecordTypeEnum::Login,
        #[Assert\NotBlank(message: 'vault.entries.errors.title_required')]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',
        #[Assert\Length(max: 255)]
        public readonly ?string $url = null,
        #[Assert\NotBlank(message: 'vault.entries.errors.encrypted_data_required')]
        public readonly string $encryptedData = '',
        #[Assert\NotBlank(message: 'vault.entries.errors.iv_required')]
        #[Assert\Length(max: 64)]
        public readonly string $iv = '',
        public readonly ?int $folderId = null,
        public readonly bool $isFavorite = false,
    ) {}

    public function getType(): VaultRecordTypeEnum
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getEncryptedData(): string
    {
        return $this->encryptedData;
    }

    public function getIv(): string
    {
        return $this->iv;
    }

    public function getFolderId(): ?int
    {
        return $this->folderId;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }
}
