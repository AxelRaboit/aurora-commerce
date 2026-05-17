<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface NotificationInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getRecipient(): CoreUserInterface;

    public function setRecipient(CoreUserInterface $user): static;

    public function getType(): string;

    public function setType(string $type): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getBody(): ?string;

    public function setBody(?string $body): static;

    public function getUrl(): ?string;

    public function setUrl(?string $url): static;

    /** @return array<string, mixed>|null */
    public function getData(): ?array;

    /** @param array<string, mixed>|null $data */
    public function setData(?array $data): static;

    public function getReadAt(): ?DateTimeImmutable;

    public function markAsRead(): static;
}
