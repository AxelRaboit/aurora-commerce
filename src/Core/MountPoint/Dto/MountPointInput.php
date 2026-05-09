<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Dto;

use Aurora\Core\MountPoint\Enum\MountPointTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class MountPointInput implements MountPointInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.mountPoints.errors.name_required')]
        #[Assert\Length(max: 100, maxMessage: 'backend.mountPoints.errors.name_too_long')]
        public readonly string $name,
        public readonly MountPointTypeEnum $type,
        #[Assert\NotBlank(message: 'backend.mountPoints.errors.host_required')]
        #[Assert\Length(max: 255, maxMessage: 'backend.mountPoints.errors.host_too_long')]
        public readonly string $host,
        public readonly ?int $port = null,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $database = null,
        public readonly ?string $sshPublicKey = null,
        public readonly ?string $sshPrivateKey = null,
        public readonly array $config = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): MountPointTypeEnum
    {
        return $this->type;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getSshPublicKey(): ?string
    {
        return $this->sshPublicKey;
    }

    public function getSshPrivateKey(): ?string
    {
        return $this->sshPrivateKey;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
