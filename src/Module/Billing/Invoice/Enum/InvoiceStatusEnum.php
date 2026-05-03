<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Enum;

enum InvoiceStatusEnum: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case Validated = 'validated';
    case Paid = 'paid';
    case Archived = 'archived';
    case CreditNote = 'credit_note';

    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft, self::NeedsReview => true,
            default => false,
        };
    }

    public function canHaveCreditNote(): bool
    {
        return match ($this) {
            self::Validated, self::Paid => true,
            default => false,
        };
    }

    public function getLabelKey(): string
    {
        return 'admin.billing.invoices.status.'.$this->value;
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::NeedsReview => 'amber',
            self::Validated => 'sky',
            self::Paid => 'emerald',
            self::Archived => 'gray',
            self::CreditNote => 'violet',
        };
    }
}
