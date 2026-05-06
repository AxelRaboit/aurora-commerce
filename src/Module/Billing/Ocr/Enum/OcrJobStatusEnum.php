<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Enum;

enum OcrJobStatusEnum: string
{
    case Queued = 'queued';
    case Extracting = 'extracting';
    case Parsing = 'parsing';
    case Completed = 'completed';
    case NeedsReview = 'needs_review';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::NeedsReview, self::Failed => true,
            default => false,
        };
    }

    public function getLabelKey(): string
    {
        return 'backend.billing.ocr.status.'.$this->value;
    }

    /** Progress percentage (0–100), null for states where progress is not meaningful. */
    public function getProgress(): ?int
    {
        return match ($this) {
            self::Extracting => 33,
            self::Parsing => 66,
            self::Completed, self::NeedsReview => 100,
            default => null,
        };
    }

    /** Tailwind colour key matching AppBadge palette (gray/sky/emerald/amber/rose/violet/slate). */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::Queued => 'slate',
            self::Extracting, self::Parsing => 'sky',
            self::Completed => 'emerald',
            self::NeedsReview => 'amber',
            self::Failed => 'rose',
        };
    }
}
