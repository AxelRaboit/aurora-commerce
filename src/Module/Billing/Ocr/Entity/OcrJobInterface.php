<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use DateTimeImmutable;

interface OcrJobInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): self;

    public function getMedia(): MediaInterface;

    public function setMedia(MediaInterface $media): self;

    public function getStatus(): OcrJobStatusEnum;

    public function setStatus(OcrJobStatusEnum $status): self;

    public function getCreatedBy(): ?User;

    public function setCreatedBy(?User $createdBy): self;

    public function getModelUsed(): ?string;

    public function setModelUsed(?string $modelUsed): self;

    public function getStartedAt(): ?DateTimeImmutable;

    public function setStartedAt(?DateTimeImmutable $startedAt): self;

    public function getFinishedAt(): ?DateTimeImmutable;

    public function setFinishedAt(?DateTimeImmutable $finishedAt): self;

    /** @return array<string, mixed>|null */
    public function getRawDoctr(): ?array;

    /** @param array<string, mixed>|null $rawDoctr */
    public function setRawDoctr(?array $rawDoctr): self;

    /** @return array<string, mixed>|null */
    public function getRawVlm(): ?array;

    /** @param array<string, mixed>|null $rawVlm */
    public function setRawVlm(?array $rawVlm): self;

    /** @return array<string, mixed>|null */
    public function getExtracted(): ?array;

    /** @param array<string, mixed>|null $extracted */
    public function setExtracted(?array $extracted): self;

    public function getConfidence(): ?float;

    public function setConfidence(?float $confidence): self;

    public function getError(): ?string;

    public function setError(?string $error): self;

    /** @return list<array{level: string, message: string, context: array<string,mixed>, ts: string}> */
    public function getLogs(): array;

    /** @param array<string, mixed> $context */
    public function appendLog(string $level, string $message, array $context = []): self;
}
