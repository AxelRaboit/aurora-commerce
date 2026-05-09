<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Entity;

use Aurora\Core\MountPoint\Enum\MountPointTypeEnum;
use Aurora\Core\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMountPoint implements MountPointInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 100)]
    protected string $name;

    #[ORM\Column(length: 20, enumType: MountPointTypeEnum::class)]
    protected MountPointTypeEnum $type;

    #[ORM\Column(length: 255)]
    protected string $host;

    #[ORM\Column(nullable: true)]
    protected ?int $port = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $database = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $sshPublicKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $sshPrivateKey = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    protected array $config = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $lastTestedAt = null;

    #[ORM\Column(nullable: true)]
    protected ?bool $lastTestSuccessful = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): MountPointTypeEnum
    {
        return $this->type;
    }

    public function setType(MountPointTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(?string $database): static
    {
        $this->database = $database;

        return $this;
    }

    public function getSshPublicKey(): ?string
    {
        return $this->sshPublicKey;
    }

    public function setSshPublicKey(?string $sshPublicKey): static
    {
        $this->sshPublicKey = $sshPublicKey;

        return $this;
    }

    public function getSshPrivateKey(): ?string
    {
        return $this->sshPrivateKey;
    }

    public function setSshPrivateKey(?string $sshPrivateKey): static
    {
        $this->sshPrivateKey = $sshPrivateKey;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getLastTestedAt(): ?DateTimeImmutable
    {
        return $this->lastTestedAt;
    }

    public function setLastTestedAt(?DateTimeImmutable $lastTestedAt): static
    {
        $this->lastTestedAt = $lastTestedAt;

        return $this;
    }

    public function isLastTestSuccessful(): ?bool
    {
        return $this->lastTestSuccessful;
    }

    public function setLastTestSuccessful(?bool $lastTestSuccessful): static
    {
        $this->lastTestSuccessful = $lastTestSuccessful;

        return $this;
    }
}
