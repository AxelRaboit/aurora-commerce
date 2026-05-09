<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Entity;

use Aurora\Core\MountPoint\Enum\MountPointTypeEnum;
use DateTimeImmutable;

interface MountPointInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getType(): MountPointTypeEnum;

    public function setType(MountPointTypeEnum $type): static;

    public function getHost(): string;

    public function setHost(string $host): static;

    public function getPort(): ?int;

    public function setPort(?int $port): static;

    public function getUsername(): ?string;

    public function setUsername(?string $username): static;

    public function getPassword(): ?string;

    public function setPassword(?string $password): static;

    public function getDatabase(): ?string;

    public function setDatabase(?string $database): static;

    public function getSshPublicKey(): ?string;

    public function setSshPublicKey(?string $sshPublicKey): static;

    public function getSshPrivateKey(): ?string;

    public function setSshPrivateKey(?string $sshPrivateKey): static;

    /** @return array<string, mixed> */
    public function getConfig(): array;

    /** @param array<string, mixed> $config */
    public function setConfig(array $config): static;

    public function getLastTestedAt(): ?DateTimeImmutable;

    public function setLastTestedAt(?DateTimeImmutable $lastTestedAt): static;

    public function isLastTestSuccessful(): ?bool;

    public function setLastTestSuccessful(?bool $lastTestSuccessful): static;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
