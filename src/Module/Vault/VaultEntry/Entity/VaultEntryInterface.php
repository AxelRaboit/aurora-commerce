<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;

interface VaultEntryInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getFolder(): ?VaultFolderInterface;

    public function setFolder(?VaultFolderInterface $folder): static;

    public function getType(): VaultRecordTypeEnum;

    public function setType(VaultRecordTypeEnum $type): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getUrl(): ?string;

    public function setUrl(?string $url): static;

    public function getEncryptedData(): string;

    public function setEncryptedData(string $encryptedData): static;

    public function getIv(): string;

    public function setIv(string $iv): static;

    public function isFavorite(): bool;

    public function setIsFavorite(bool $isFavorite): static;
}
