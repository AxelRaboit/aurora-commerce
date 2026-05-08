<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Entity;

use Aurora\Core\Auth\Enum\AccessRequestStatusEnum;
use Aurora\Core\Contract\TimestampableInterface;
use DateTimeImmutable;

interface AccessRequestInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getToken(): string;

    public function getRequesterEmail(): string;

    public function getRequesterName(): ?string;

    public function setRequesterName(?string $requesterName): static;

    public function getMessage(): ?string;

    public function setMessage(?string $message): static;

    public function getStatus(): AccessRequestStatusEnum;

    public function setStatus(AccessRequestStatusEnum $status): static;

    public function getExpiresAt(): DateTimeImmutable;

    public function isPending(): bool;

    public function isApproved(): bool;

    public function isRejected(): bool;

    public function isExpired(): bool;
}
