<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum BillingSettingEnum: string implements ApplicationParameterEnumInterface
{
    case InvoicePrefix = 'backend_billing_invoice_prefix';
    case CreditNotePrefix = 'backend_billing_credit_note_prefix';
    case TiersPrefix = 'backend_billing_tiers_prefix';
    case OcrJobPrefix = 'backend_billing_ocr_job_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::InvoicePrefix => 'backend.parameters.billing_invoice_prefix.label',
            self::CreditNotePrefix => 'backend.parameters.billing_credit_note_prefix.label',
            self::TiersPrefix => 'backend.parameters.billing_tiers_prefix.label',
            self::OcrJobPrefix => 'backend.parameters.billing_ocr_job_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::InvoicePrefix => 'backend.parameters.billing_invoice_prefix.description',
            self::CreditNotePrefix => 'backend.parameters.billing_credit_note_prefix.description',
            self::TiersPrefix => 'backend.parameters.billing_tiers_prefix.description',
            self::OcrJobPrefix => 'backend.parameters.billing_ocr_job_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::InvoicePrefix => SequencePrefixEnum::Invoice->value,
            self::CreditNotePrefix => SequencePrefixEnum::CreditNote->value,
            self::TiersPrefix => SequencePrefixEnum::Tiers->value,
            self::OcrJobPrefix => SequencePrefixEnum::OcrJob->value,
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return 'sequences';
    }
}
