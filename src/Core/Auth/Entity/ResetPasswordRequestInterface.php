<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Entity;

use Aurora\Core\User\Entity\User;
use DateTimeImmutable;

interface ResetPasswordRequestInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getUser(): User;

    public function getSelector(): string;

    public function getHashedToken(): string;

    public function getExpiresAt(): DateTimeImmutable;

    public function isExpired(): bool;
}
