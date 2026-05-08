<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Entity;

use DateTimeImmutable;

interface AuditLogInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getModule(): string;

    public function getAction(): string;

    public function getEntityType(): ?string;

    public function getEntityId(): ?int;

    public function getUserId(): ?int;

    public function getUserEmail(): ?string;

    public function getUserName(): ?string;

    /** @return array<string, mixed>|null */
    public function getData(): ?array;

    public function getCreatedAt(): DateTimeImmutable;
}
