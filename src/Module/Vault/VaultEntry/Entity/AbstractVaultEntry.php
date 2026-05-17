<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Vault\Enum\VaultRecordTypeEnum;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractVaultEntry implements VaultEntryInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    #[ORM\ManyToOne(targetEntity: VaultFolderInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?VaultFolderInterface $folder = null;

    #[ORM\Column(length: 50, enumType: VaultRecordTypeEnum::class)]
    protected VaultRecordTypeEnum $type;

    #[ORM\Column(length: 255)]
    protected string $title;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(type: Types::TEXT)]
    protected string $encryptedData;

    #[ORM\Column(length: 64)]
    protected string $iv;

    #[ORM\Column]
    protected bool $isFavorite = false;

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFolder(): ?VaultFolderInterface
    {
        return $this->folder;
    }

    public function setFolder(?VaultFolderInterface $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getType(): VaultRecordTypeEnum
    {
        return $this->type;
    }

    public function setType(VaultRecordTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getEncryptedData(): string
    {
        return $this->encryptedData;
    }

    public function setEncryptedData(string $encryptedData): static
    {
        $this->encryptedData = $encryptedData;

        return $this;
    }

    public function getIv(): string
    {
        return $this->iv;
    }

    public function setIv(string $iv): static
    {
        $this->iv = $iv;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }
}
